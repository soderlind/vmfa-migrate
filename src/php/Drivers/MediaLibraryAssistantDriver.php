<?php
/**
 * Media Library Assistant migration driver.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Driver for Media Library Assistant.
 *
 * MLA uses the WordPress taxonomy 'attachment_category' for media folders.
 *
 * @link https://wordpress.org/plugins/media-library-assistant/
 */
final class MediaLibraryAssistantDriver extends AbstractTaxonomyDriver {

	/**
	 * Get the driver slug.
	 *
	 * @inheritDoc
	 */
	public static function slug(): string {
		return 'media-library-assistant';
	}

	/**
	 * Get the driver display label.
	 *
	 * @inheritDoc
	 */
	public static function label(): string {
		return 'Media Library Assistant';
	}

	/**
	 * Get the source taxonomy slug.
	 *
	 * @inheritDoc
	 */
	protected function get_taxonomy(): string {
		return 'attachment_category';
	}
}
