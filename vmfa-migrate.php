<?php
/**
 * Plugin Name:       Virtual Media Folders - Migrate
 * Plugin URI:        https://github.com/soderlind/vmfa-migrate
 * Description:       Migration add-on for Virtual Media Folders. Import folders and assignments from Enhanced Media Library, FileBird, Real Media Library, HappyFiles, WP Media Folder, Media Library Assistant, and CatFolders.
 * Version:           0.3.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Requires Plugins:  virtual-media-folders
 * Author:            Per Soderlind
 * Author URI:        https://soderlind.no
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vmfa-migrate
 * Domain Path:       /languages
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate;

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'VMFA_MIGRATE_VERSION', '0.3.0' );
define( 'VMFA_MIGRATE_FILE', __FILE__ );
define( 'VMFA_MIGRATE_PATH', plugin_dir_path( __FILE__ ) );
define( 'VMFA_MIGRATE_URL', plugin_dir_url( __FILE__ ) );
define( 'VMFA_MIGRATE_BASENAME', plugin_basename( __FILE__ ) );

// Require Composer autoloader.
if ( file_exists( VMFA_MIGRATE_PATH . 'vendor/autoload.php' ) ) {
	require_once VMFA_MIGRATE_PATH . 'vendor/autoload.php';
}

// Initialize Action Scheduler early (must be loaded before plugins_loaded).
use VirtualMediaFolders\Addon\ActionSchedulerLoader;

ActionSchedulerLoader::maybe_load( VMFA_MIGRATE_PATH );

/**
 * Initialize the plugin.
 *
 * @return void
 */
function init(): void {
	// Update checker via GitHub releases.
	if ( ! class_exists( \Soderlind\WordPress\GitHubUpdater::class ) ) {
		require_once __DIR__ . '/class-github-updater.php';
	}
	\Soderlind\WordPress\GitHubUpdater::init(
		github_url: 'https://github.com/soderlind/vmfa-migrate',
		plugin_file: VMFA_MIGRATE_FILE,
		plugin_slug: 'vmfa-migrate',
		name_regex: '/vmfa-migrate\.zip/',
		branch: 'main',
	);

	// Boot the plugin.
	Plugin::get_instance()->init();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init', 20 );
