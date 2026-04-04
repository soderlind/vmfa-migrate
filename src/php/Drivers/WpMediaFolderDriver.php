<?php
/**
 * WP Media Folder (JoomUnited) migration driver.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Driver for WP Media Folder by JoomUnited.
 *
 * WPMF uses the WordPress taxonomy 'wpmf-category' for media folders.
 *
 * @link https://www.joomunited.com/wordpress-products/wp-media-folder
 */
final class WpMediaFolderDriver extends AbstractTaxonomyDriver {

	/**
	 * Get the driver slug.
	 *
	 * @inheritDoc
	 */
	public static function slug(): string {
		return 'wp-media-folder';
	}

	/**
	 * Get the driver display label.
	 *
	 * @inheritDoc
	 */
	public static function label(): string {
		return 'WP Media Folder';
	}

	/**
	 * Get the source taxonomy slug.
	 *
	 * @inheritDoc
	 */
	protected function get_taxonomy(): string {
		return 'wpmf-category';
	}
}
