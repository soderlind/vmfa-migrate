<?php
/**
 * Settings tab for the Migration add-on.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Admin;

defined( 'ABSPATH' ) || exit;

use VirtualMediaFolders\Addon\AbstractSettingsTab;

/**
 * Migration tab in the VMF settings page.
 */
final class SettingsTab extends AbstractSettingsTab {

	/**
	 * Get the tab slug.
	 *
	 * @inheritDoc
	 */
	protected function get_tab_slug(): string {
		return 'migrate';
	}

	/**
	 * Get the tab display label.
	 *
	 * @inheritDoc
	 */
	protected function get_tab_label(): string {
		return __( 'Migration', 'vmfa-migrate' );
	}

	/**
	 * Get the text domain.
	 *
	 * @inheritDoc
	 */
	protected function get_text_domain(): string {
		return 'vmfa-migrate';
	}

	/**
	 * Get the build directory path.
	 *
	 * @inheritDoc
	 */
	protected function get_build_path(): string {
		return VMFA_MIGRATE_PATH . 'build/';
	}

	/**
	 * Get the build directory URL.
	 *
	 * @inheritDoc
	 */
	protected function get_build_url(): string {
		return VMFA_MIGRATE_URL . 'build/';
	}

	/**
	 * Get the languages directory path.
	 *
	 * @inheritDoc
	 */
	protected function get_languages_path(): string {
		return VMFA_MIGRATE_PATH . 'languages';
	}

	/**
	 * Get the plugin version.
	 *
	 * @inheritDoc
	 */
	protected function get_plugin_version(): string {
		return VMFA_MIGRATE_VERSION;
	}

	/**
	 * Get the localized script name.
	 *
	 * @inheritDoc
	 */
	protected function get_localized_name(): string {
		return 'vmfaMigrate';
	}

	/**
	 * Get the localized script data.
	 *
	 * @inheritDoc
	 */
	protected function get_localized_data(): array {
		return [
			'restUrl' => rest_url( 'vmfa-migrate/v1/' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		];
	}
}
