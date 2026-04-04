<?php
/**
 * Enhanced Media Library migration driver.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Driver for Enhanced Media Library.
 *
 * EML uses the WordPress taxonomy 'media_category' for media folders.
 *
 * @link https://wordpress.org/plugins/enhanced-media-library/
 */
final class EnhancedMediaLibraryDriver extends AbstractTaxonomyDriver {

	/**
	 * Get the driver slug.
	 *
	 * @inheritDoc
	 */
	public static function slug(): string {
		return 'enhanced-media-library';
	}

	/**
	 * Get the driver display label.
	 *
	 * @inheritDoc
	 */
	public static function label(): string {
		return 'Enhanced Media Library';
	}

	/**
	 * Get the source taxonomy slug.
	 *
	 * @inheritDoc
	 */
	protected function get_taxonomy(): string {
		return 'media_category';
	}
}
