<?php
/**
 * Real Media Library migration driver.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Driver for Real Media Library.
 *
 * RML stores folders in custom table `{prefix}realmedialibrary` and assignments
 * in `{prefix}realmedialibrary_posts`.
 *
 * @link https://wordpress.org/plugins/real-media-library-lite/
 */
final class RealMediaLibraryDriver extends AbstractTableDriver {

	/**
	 * Get the driver slug.
	 *
	 * @inheritDoc
	 */
	public static function slug(): string {
		return 'real-media-library';
	}

	/**
	 * Get the driver display label.
	 *
	 * @inheritDoc
	 */
	public static function label(): string {
		return 'Real Media Library';
	}

	/**
	 * Get the folder table name.
	 *
	 * @inheritDoc
	 */
	protected function get_folder_table(): string {
		return 'realmedialibrary';
	}

	/**
	 * Get the junction table name.
	 *
	 * @inheritDoc
	 */
	protected function get_junction_table(): string {
		return 'realmedialibrary_posts';
	}

	/**
	 * Get the folder ID column name.
	 *
	 * @inheritDoc
	 */
	protected function get_folder_id_column(): string {
		return 'id';
	}

	/**
	 * Get the folder name column.
	 *
	 * @inheritDoc
	 */
	protected function get_folder_name_column(): string {
		return 'name';
	}

	/**
	 * Get the parent column name.
	 *
	 * @inheritDoc
	 */
	protected function get_folder_parent_column(): string {
		return 'parent';
	}

	/**
	 * Get the attachment ID column.
	 *
	 * @inheritDoc
	 */
	protected function get_junction_attachment_column(): string {
		return 'attachment';
	}

	/**
	 * Get the folder ID junction column.
	 *
	 * @inheritDoc
	 */
	protected function get_junction_folder_column(): string {
		return 'fid';
	}
}
