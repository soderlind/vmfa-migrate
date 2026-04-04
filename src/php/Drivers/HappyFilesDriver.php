<?php
/**
 * HappyFiles migration driver.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Driver for HappyFiles.
 *
 * HappyFiles uses the WordPress taxonomy 'happyfiles_category' for media folders.
 *
 * @link https://wordpress.org/plugins/happyfiles/
 */
final class HappyFilesDriver extends AbstractTaxonomyDriver {

	/**
	 * Get the driver slug.
	 *
	 * @inheritDoc
	 */
	public static function slug(): string {
		return 'happyfiles';
	}

	/**
	 * Get the driver display label.
	 *
	 * @inheritDoc
	 */
	public static function label(): string {
		return 'HappyFiles';
	}

	/**
	 * Get the source taxonomy slug.
	 *
	 * @inheritDoc
	 */
	protected function get_taxonomy(): string {
		return 'happyfiles_category';
	}
}
