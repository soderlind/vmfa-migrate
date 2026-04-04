<?php
/**
 * WP-CLI commands for migration.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\CLI;

defined( 'ABSPATH' ) || exit;

use VmfaMigrate\Plugin;
use WP_CLI;

/**
 * Migrate media folders from other plugins into Virtual Media Folders.
 *
 * ## EXAMPLES
 *
 *     # List available migration sources.
 *     wp vmfa-migrate list-sources
 *
 *     # Preview what will be migrated.
 *     wp vmfa-migrate preview enhanced-media-library
 *
 *     # Run the migration.
 *     wp vmfa-migrate run enhanced-media-library
 *
 *     # Dry-run (preview + stats only).
 *     wp vmfa-migrate run enhanced-media-library --dry-run
 */
final class MigrateCommand {

	/**
	 * List detected migration sources.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function list_sources( array $args, array $assoc_args ): void {
		$detector = Plugin::get_instance()->get_detector();
		$sources  = $detector->get_available_sources();

		if ( empty( $sources ) ) {
			WP_CLI::warning( __( 'No compatible migration sources detected.', 'vmfa-migrate' ) );
			return;
		}

		$format = $assoc_args['format'] ?? 'table';

		WP_CLI\Utils\format_items(
			$format,
			$sources,
			[ 'slug', 'label', 'folder_count', 'assignment_count' ]
		);
	}

	/**
	 * Preview a migration source.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Migration source slug (from list-sources).
	 *
	 * [--format=<format>]
	 * : Output format for the folder tree.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - tree
	 * ---
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function preview( array $args, array $assoc_args ): void {
		$slug      = $args[0];
		$migration = Plugin::get_instance()->get_migration();
		$result    = $migration->preview( $slug );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$format = $assoc_args['format'] ?? 'table';

		WP_CLI::log( '' );
		WP_CLI::log(
			sprintf(
				/* translators: 1: folder count, 2: assignment count */
				__( 'Source: %1$d folders, %2$d assignments', 'vmfa-migrate' ),
				$result['stats']['folder_count'],
				$result['stats']['assignment_count']
			)
		);
		WP_CLI::log( '' );

		if ( 'json' === $format ) {
			WP_CLI::log( wp_json_encode( $result['folders'], JSON_PRETTY_PRINT ) );
			return;
		}

		if ( 'tree' === $format ) {
			$this->print_tree( $result['folders'] );
			return;
		}

		WP_CLI\Utils\format_items(
			'table',
			$result['folders'],
			[ 'id', 'name', 'slug', 'parent_id' ]
		);
	}

	/**
	 * Run a migration.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Migration source slug (from list-sources).
	 *
	 * [--dry-run]
	 * : Show what would be migrated without making changes.
	 *
	 * [--batch-size=<number>]
	 * : Number of assignments per batch.
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--conflict=<strategy>]
	 * : How to handle existing VMF folders with the same name.
	 * ---
	 * default: skip
	 * options:
	 *   - skip
	 *   - merge
	 *   - overwrite
	 * ---
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function run( array $args, array $assoc_args ): void {
		$slug = $args[0];

		if ( isset( $assoc_args['dry-run'] ) && $assoc_args['dry-run'] ) {
			$this->preview( $args, $assoc_args );
			WP_CLI::success( __( 'Dry run complete. No changes made.', 'vmfa-migrate' ) );
			return;
		}

		$migration = Plugin::get_instance()->get_migration();

		$options = [
			'batch_size'        => isset( $assoc_args['batch-size'] ) ? absint( $assoc_args['batch-size'] ) : 100,
			'conflict_strategy' => $assoc_args['conflict'] ?? 'skip',
		];

		WP_CLI::log( __( 'Starting migration…', 'vmfa-migrate' ) );

		$result = $migration->start( $slug, $options );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::log(
			sprintf(
				/* translators: %d: number of folders created */
				__( '%d folders created.', 'vmfa-migrate' ),
				$result['folders_created']
			)
		);

		// Poll job progress.
		$job_id = $result['job_id'];
		$this->poll_job( $job_id );
	}

	/**
	 * Poll a job until completion, showing a progress bar.
	 *
	 * @param string $job_id Job ID.
	 * @return void
	 */
	private function poll_job( string $job_id ): void {
		$migration = Plugin::get_instance()->get_migration();
		$bar       = null;

		while ( true ) {
			// Process pending Action Scheduler actions synchronously in CLI.
			if ( function_exists( 'as_has_scheduled_action' ) ) {
				do_action( 'action_scheduler_run_queue', 'Async Request' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			}

			$job = $migration->get_job( $job_id );

			if ( is_wp_error( $job ) ) {
				WP_CLI::error( $job->get_error_message() );
			}

			if ( null === $bar && $job['total'] > 0 ) {
				$bar = WP_CLI\Utils\make_progress_bar(
					__( 'Migrating assignments', 'vmfa-migrate' ),
					$job['total']
				);
			}

			if ( $bar ) {
				// Update progress bar to current position.
				$bar->tick( 0 );
			}

			if ( in_array( $job['status'], [ 'completed', 'cancelled', 'error' ], true ) ) {
				break;
			}

			sleep( 1 );
		}

		if ( $bar ) {
			$bar->finish();
		}

		WP_CLI::log( '' );
		WP_CLI::log(
			sprintf(
				/* translators: 1: assigned, 2: skipped, 3: errors */
				__( 'Results: %1$d assigned, %2$d skipped, %3$d errors.', 'vmfa-migrate' ),
				$job['assigned'],
				$job['skipped'],
				$job['errors']
			)
		);

		if ( 'completed' === $job['status'] ) {
			WP_CLI::success( __( 'Migration completed.', 'vmfa-migrate' ) );
		} elseif ( 'cancelled' === $job['status'] ) {
			WP_CLI::warning( __( 'Migration was cancelled.', 'vmfa-migrate' ) );
		} else {
			WP_CLI::error( __( 'Migration encountered errors.', 'vmfa-migrate' ) );
		}
	}

	/**
	 * Print a tree representation of folders.
	 *
	 * @param array $folders Flat folder array.
	 * @return void
	 */
	private function print_tree( array $folders ): void {
		// Build lookup.
		$by_parent = [];
		foreach ( $folders as $folder ) {
			$by_parent[ $folder['parent_id'] ][] = $folder;
		}

		$this->print_tree_level( $by_parent, 0, '' );
	}

	/**
	 * Recursively print tree levels.
	 *
	 * @param array  $by_parent Folders grouped by parent_id.
	 * @param int    $parent_id Current parent.
	 * @param string $prefix    Indentation prefix.
	 * @return void
	 */
	private function print_tree_level( array $by_parent, int $parent_id, string $prefix ): void {
		$children = $by_parent[ $parent_id ] ?? [];
		$count    = count( $children );

		foreach ( $children as $i => $folder ) {
			$is_last   = ( $i === $count - 1 );
			$connector = $is_last ? '└── ' : '├── ';
			WP_CLI::log( $prefix . $connector . $folder['name'] );

			$child_prefix = $prefix . ( $is_last ? '    ' : '│   ' );
			$this->print_tree_level( $by_parent, $folder['id'], $child_prefix );
		}
	}
}
