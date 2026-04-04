<?php
/**
 * Main plugin class.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate;

defined( 'ABSPATH' ) || exit;

use VirtualMediaFolders\Addon\AbstractPlugin;
use VmfaMigrate\Admin\SettingsTab;
use VmfaMigrate\REST\MigrationController;
use VmfaMigrate\Services\DetectorService;
use VmfaMigrate\Services\MigrationService;

/**
 * Plugin bootstrap class.
 */
final class Plugin extends AbstractPlugin {

	/**
	 * Detector service instance.
	 *
	 * @var ?DetectorService
	 */
	private ?DetectorService $detector = null;

	/**
	 * Migration service instance.
	 *
	 * @var ?MigrationService
	 */
	private ?MigrationService $migration = null;

	/**
	 * Settings tab instance.
	 *
	 * @var ?SettingsTab
	 */
	private ?SettingsTab $settings_tab = null;

	/**
	 * Get the text domain.
	 *
	 * @inheritDoc
	 */
	protected function get_text_domain(): string {
		return 'vmfa-migrate';
	}

	/**
	 * Get the main plugin file path.
	 *
	 * @inheritDoc
	 */
	protected function get_plugin_file(): string {
		return VMFA_MIGRATE_FILE;
	}

	/**
	 * Initialize plugin services.
	 *
	 * @inheritDoc
	 */
	protected function init_services(): void {
		$this->detector     = new DetectorService();
		$this->migration    = new MigrationService( $this->detector );
		$this->settings_tab = new SettingsTab();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @inheritDoc
	 */
	protected function init_hooks(): void {
		// Admin hooks.
		if ( is_admin() ) {
			if ( $this->supports_parent_tabs() ) {
				add_filter( 'vmfo_settings_tabs', array( $this->settings_tab, 'register_tab' ) );
				add_action( 'vmfo_settings_enqueue_scripts', array( $this->settings_tab, 'enqueue_tab_scripts' ), 10, 2 );
			} else {
				add_action( 'admin_menu', array( $this->settings_tab, 'register_admin_menu' ) );
				add_action( 'admin_enqueue_scripts', array( $this->settings_tab, 'enqueue_admin_assets' ) );
			}
		}

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Action Scheduler hooks.
		add_action( 'vmfa_migrate_process_batch', array( $this->migration, 'process_batch' ), 10, 2 );
	}

	/**
	 * Register WP-CLI commands.
	 *
	 * @inheritDoc
	 */
	protected function init_cli(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'vmfa-migrate', CLI\MigrateCommand::class );
		}
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$controller = new MigrationController( $this->detector, $this->migration );
		$controller->register_routes();
	}

	/**
	 * Get the detector service.
	 *
	 * @return DetectorService
	 */
	public function get_detector(): DetectorService {
		return $this->detector;
	}

	/**
	 * Get the migration service.
	 *
	 * @return MigrationService
	 */
	public function get_migration(): MigrationService {
		return $this->migration;
	}
}
