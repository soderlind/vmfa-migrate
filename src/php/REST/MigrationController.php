<?php
/**
 * REST API controller for migration endpoints.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\REST;

defined( 'ABSPATH' ) || exit;

use VmfaMigrate\Services\DetectorService;
use VmfaMigrate\Services\MigrationService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST routes under vmfa-migrate/v1.
 */
final class MigrationController extends WP_REST_Controller {

	/**
	 * Route namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'vmfa-migrate/v1';

	/**
	 * Detector service instance.
	 *
	 * @var DetectorService
	 */
	private DetectorService $detector;

	/**
	 * Migration service instance.
	 *
	 * @var MigrationService
	 */
	private MigrationService $migration;

	/**
	 * Constructor.
	 *
	 * @param DetectorService  $detector  Detector service.
	 * @param MigrationService $migration Migration service.
	 */
	public function __construct( DetectorService $detector, MigrationService $migration ) {
		$this->detector  = $detector;
		$this->migration = $migration;
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /sources — list detected migration sources.
		register_rest_route(
			$this->namespace,
			'/sources',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_sources' ],
				'permission_callback' => [ $this, 'check_permission' ],
			]
		);

		// GET /sources/{slug}/preview — dry-run preview.
		register_rest_route(
			$this->namespace,
			'/sources/(?P<slug>[a-z0-9-]+)/preview',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_preview' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'slug' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);

		// POST /sources/{slug}/migrate — start migration.
		register_rest_route(
			$this->namespace,
			'/sources/(?P<slug>[a-z0-9-]+)/migrate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'start_migration' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'slug'              => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
					'batch_size'        => [
						'type'              => 'integer',
						'default'           => 100,
						'sanitize_callback' => 'absint',
					],
					'conflict_strategy' => [
						'type'              => 'string',
						'default'           => 'skip',
						'enum'              => [ 'skip', 'merge', 'overwrite' ],
						'sanitize_callback' => 'sanitize_key',
					],
					'include_taxonomies' => [
						'type'    => 'boolean',
						'default' => false,
					],
				],
			]
		);

		// GET /jobs/{id} — job progress.
		register_rest_route(
			$this->namespace,
			'/jobs/(?P<id>[a-f0-9-]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_job' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		// DELETE /jobs/{id} — cancel job.
		register_rest_route(
			$this->namespace,
			'/jobs/(?P<id>[a-f0-9-]+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'cancel_job' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Permission check — requires manage_options.
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /sources
	 *
	 * @return WP_REST_Response
	 */
	public function get_sources(): WP_REST_Response {
		return rest_ensure_response( $this->detector->get_available_sources() );
	}

	/**
	 * GET /sources/{slug}/preview
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_preview( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$slug   = $request->get_param( 'slug' );
		$result = $this->migration->preview( $slug );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * POST /sources/{slug}/migrate
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function start_migration( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$slug    = $request->get_param( 'slug' );
		$options = [
			'batch_size'         => $request->get_param( 'batch_size' ),
			'conflict_strategy'  => $request->get_param( 'conflict_strategy' ),
			'include_taxonomies' => $request->get_param( 'include_taxonomies' ),
		];

		$result = $this->migration->start( $slug, $options );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * GET /jobs/{id}
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_job( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$job_id = $request->get_param( 'id' );
		$result = $this->migration->get_job( $job_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * DELETE /jobs/{id}
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function cancel_job( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$job_id = $request->get_param( 'id' );
		$result = $this->migration->cancel_job( $job_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( [ 'cancelled' => true ] );
	}
}
