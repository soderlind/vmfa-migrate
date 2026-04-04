<?php
/**
 * Migration service — orchestrates folder and assignment migration.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Services;

defined( 'ABSPATH' ) || exit;

use VmfaMigrate\Drivers\DriverInterface;

/**
 * Handles the migration pipeline: preview, batch folder creation, and
 * batched attachment assignment via Action Scheduler.
 */
final class MigrationService {

	private const VMF_TAXONOMY = 'vmfo_folder';
	private const JOB_PREFIX   = 'vmfa_migrate_job_';
	private const BATCH_ACTION = 'vmfa_migrate_process_batch';

	/**
	 * Detector service instance.
	 *
	 * @var DetectorService
	 */
	private DetectorService $detector;

	/**
	 * Constructor.
	 *
	 * @param DetectorService $detector Detector service instance.
	 */
	public function __construct( DetectorService $detector ) {
		$this->detector = $detector;
	}

	/**
	 * Dry-run preview for a migration source.
	 *
	 * @param string $driver_slug Source driver slug.
	 * @return array{folders: array, stats: array}|\WP_Error
	 */
	public function preview( string $driver_slug ): array|\WP_Error {
		$driver = $this->detector->get_driver( $driver_slug );

		if ( ! $driver ) {
			return new \WP_Error(
				'vmfa_migrate_unknown_source',
				__( 'Unknown migration source.', 'vmfa-migrate' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! $driver->is_available() ) {
			return new \WP_Error(
				'vmfa_migrate_source_unavailable',
				__( 'Migration source data not found.', 'vmfa-migrate' ),
				[ 'status' => 404 ]
			);
		}

		return [
			'folders' => $driver->get_folder_tree(),
			'stats'   => $driver->get_stats(),
		];
	}

	/**
	 * Start a migration job.
	 *
	 * Creates VMF folders synchronously (fast), then schedules batched
	 * attachment assignment via Action Scheduler.
	 *
	 * @param string               $driver_slug Source driver slug.
	 * @param array<string, mixed> $options     Migration options.
	 * @return array{job_id: string, folders_created: int}|\WP_Error
	 */
	public function start( string $driver_slug, array $options = [] ): array|\WP_Error {
		$driver = $this->detector->get_driver( $driver_slug );

		if ( ! $driver ) {
			return new \WP_Error(
				'vmfa_migrate_unknown_source',
				__( 'Unknown migration source.', 'vmfa-migrate' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! $driver->is_available() ) {
			return new \WP_Error(
				'vmfa_migrate_source_unavailable',
				__( 'Migration source data not found.', 'vmfa-migrate' ),
				[ 'status' => 404 ]
			);
		}

		$batch_size = isset( $options['batch_size'] ) ? absint( $options['batch_size'] ) : 100;
		$conflict   = isset( $options['conflict_strategy'] ) ? sanitize_key( $options['conflict_strategy'] ) : 'skip';

		if ( $batch_size < 1 ) {
			$batch_size = 100;
		}

		if ( ! in_array( $conflict, [ 'skip', 'merge', 'overwrite' ], true ) ) {
			$conflict = 'skip';
		}

		// Phase 1: Create VMF folders (synchronous — typically fast).
		$folder_map      = $this->create_vmf_folders( $driver, $conflict );
		$folders_created = count( array_filter( $folder_map, fn( $v ) => $v['created'] ) );

		// Phase 2: Schedule batched attachment assignment.
		$job_id = wp_generate_uuid4();
		$stats  = $driver->get_stats();

		$job_data = [
			'driver_slug'     => $driver_slug,
			'folder_map'      => $folder_map,
			'batch_size'      => $batch_size,
			'conflict'        => $conflict,
			'total'           => $stats['assignment_count'],
			'processed'       => 0,
			'skipped'         => 0,
			'assigned'        => 0,
			'errors'          => 0,
			'status'          => 'running',
			'folders_created' => $folders_created,
			'started_at'      => time(),
			'completed_at'    => null,
		];

		update_option( self::JOB_PREFIX . $job_id, $job_data, false );

		// Schedule first batch.
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action(
				time(),
				self::BATCH_ACTION,
				[ $job_id, 0 ],
				'vmfa-migrate'
			);
		} else {
			// Fallback: process synchronously.
			$this->process_batch( $job_id, 0 );
		}

		return [
			'job_id'          => $job_id,
			'folders_created' => $folders_created,
		];
	}

	/**
	 * Process a batch of attachment assignments.
	 *
	 * Called by Action Scheduler or synchronously as fallback.
	 *
	 * @param string $job_id Job identifier.
	 * @param int    $offset Current offset in the assignment list.
	 * @return void
	 */
	public function process_batch( string $job_id, int $offset ): void {
		$job = get_option( self::JOB_PREFIX . $job_id );

		if ( ! $job || 'running' !== $job['status'] ) {
			return;
		}

		$driver = $this->detector->get_driver( $job['driver_slug'] );

		if ( ! $driver ) {
			$job['status'] = 'error';
			update_option( self::JOB_PREFIX . $job_id, $job, false );
			return;
		}

		$folder_map = $job['folder_map'];
		$batch_size = $job['batch_size'];
		$current    = 0;
		$processed  = 0;

		foreach ( $driver->get_assignments() as $assignment ) {
			// Skip to offset.
			if ( $current < $offset ) {
				++$current;
				continue;
			}

			// Stop at batch limit.
			if ( $processed >= $batch_size ) {
				break;
			}

			$source_id   = $assignment['source_folder_id'];
			$attach_id   = $assignment['attachment_id'];
			$vmf_term_id = $folder_map[ $source_id ]['vmf_term_id'] ?? null;

			if ( null === $vmf_term_id ) {
				++$job['skipped'];
			} else {
				// Check if attachment already has a VMF folder.
				$existing = wp_get_object_terms( $attach_id, self::VMF_TAXONOMY, [ 'fields' => 'ids' ] );

				if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
					++$job['skipped'];
				} else {
					$result = wp_set_object_terms( $attach_id, $vmf_term_id, self::VMF_TAXONOMY );

					if ( is_wp_error( $result ) ) {
						++$job['errors'];
					} else {
						++$job['assigned'];
					}
				}
			}

			++$processed;
			++$current;
		}

		$job['processed'] += $processed;

		// Check if done.
		if ( $job['processed'] >= $job['total'] || 0 === $processed ) {
			$job['status']       = 'completed';
			$job['completed_at'] = time();
		}

		update_option( self::JOB_PREFIX . $job_id, $job, false );

		// Schedule next batch if still running.
		if ( 'running' === $job['status'] && function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action(
				time(),
				self::BATCH_ACTION,
				[ $job_id, $offset + $processed ],
				'vmfa-migrate'
			);
		} elseif ( 'running' === $job['status'] ) {
			// Synchronous fallback (recursive).
			$this->process_batch( $job_id, $offset + $processed );
		}
	}

	/**
	 * Get job status.
	 *
	 * @param string $job_id Job identifier.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function get_job( string $job_id ): array|\WP_Error {
		$job = get_option( self::JOB_PREFIX . $job_id );

		if ( ! $job ) {
			return new \WP_Error(
				'vmfa_migrate_job_not_found',
				__( 'Migration job not found.', 'vmfa-migrate' ),
				[ 'status' => 404 ]
			);
		}

		return [
			'job_id'          => $job_id,
			'status'          => $job['status'],
			'total'           => $job['total'],
			'processed'       => $job['processed'],
			'assigned'        => $job['assigned'],
			'skipped'         => $job['skipped'],
			'errors'          => $job['errors'],
			'folders_created' => $job['folders_created'],
			'started_at'      => $job['started_at'],
			'completed_at'    => $job['completed_at'],
		];
	}

	/**
	 * Cancel a running job.
	 *
	 * @param string $job_id Job identifier.
	 * @return true|\WP_Error
	 */
	public function cancel_job( string $job_id ): true|\WP_Error {
		$job = get_option( self::JOB_PREFIX . $job_id );

		if ( ! $job ) {
			return new \WP_Error(
				'vmfa_migrate_job_not_found',
				__( 'Migration job not found.', 'vmfa-migrate' ),
				[ 'status' => 404 ]
			);
		}

		$job['status']       = 'cancelled';
		$job['completed_at'] = time();
		update_option( self::JOB_PREFIX . $job_id, $job, false );

		// Unschedule pending batches.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::BATCH_ACTION, [ $job_id ], 'vmfa-migrate' );
		}

		return true;
	}

	/**
	 * Create VMF folder terms mirroring the source folder tree.
	 *
	 * Returns a map of source_folder_id => [ vmf_term_id, created ].
	 *
	 * @param DriverInterface $driver   Source driver.
	 * @param string          $conflict Conflict strategy ('skip', 'merge', 'overwrite').
	 * @return array<int, array{vmf_term_id: int, created: bool}>
	 */
	private function create_vmf_folders( DriverInterface $driver, string $conflict ): array {
		$source_tree = $driver->get_folder_tree();
		$folder_map  = [];

		// Build a lookup of source folders by their IDs.
		$by_id = [];
		foreach ( $source_tree as $folder ) {
			$by_id[ $folder['id'] ] = $folder;
		}

		// Process in order that respects parent-child relationships.
		$processed = [];
		$queue     = array_keys( $by_id );

		// Simple topological sorting: process parents before children.
		$max_iterations = count( $queue ) * 2;
		$iteration      = 0;

		while ( ! empty( $queue ) && $iteration < $max_iterations ) {
			++$iteration;
			$id     = array_shift( $queue );
			$folder = $by_id[ $id ];

			// If parent hasn't been processed yet and it's not root, defer.
			if ( $folder['parent_id'] > 0 && ! isset( $processed[ $folder['parent_id'] ] ) ) {
				$queue[] = $id;
				continue;
			}

			// Determine VMF parent term ID.
			$vmf_parent = 0;
			if ( $folder['parent_id'] > 0 && isset( $folder_map[ $folder['parent_id'] ] ) ) {
				$vmf_parent = $folder_map[ $folder['parent_id'] ]['vmf_term_id'];
			}

			// Check if a VMF folder with the same name and parent exists.
			$existing = get_term_by( 'name', $folder['name'], self::VMF_TAXONOMY );

			if ( $existing && $existing->parent === $vmf_parent ) {
				if ( 'overwrite' === $conflict ) {
					// Create with a deduplicated name.
					$dedup_name = $folder['name'] . ' (' . $driver::label() . ')';
					$result     = wp_insert_term( $dedup_name, self::VMF_TAXONOMY, [ 'parent' => $vmf_parent ] );

					if ( ! is_wp_error( $result ) ) {
						$folder_map[ $id ] = [
							'vmf_term_id' => $result['term_id'],
							'created'     => true,
						];
					}
				} else {
					// 'skip' or 'merge': reuse existing.
					$folder_map[ $id ] = [
						'vmf_term_id' => $existing->term_id,
						'created'     => false,
					];
				}
			} else {
				$result = wp_insert_term(
					$folder['name'],
					self::VMF_TAXONOMY,
					[
						'parent' => $vmf_parent,
						'slug'   => $folder['slug'],
					]
				);

				if ( is_wp_error( $result ) ) {
					// Term might exist with same slug but different name.
					if ( 'term_exists' === $result->get_error_code() ) {
						$existing_id       = (int) $result->get_error_data();
						$folder_map[ $id ] = [
							'vmf_term_id' => $existing_id,
							'created'     => false,
						];
					}
				} else {
					$folder_map[ $id ] = [
						'vmf_term_id' => $result['term_id'],
						'created'     => true,
					];
				}
			}

			$processed[ $id ] = true;
		}

		return $folder_map;
	}
}
