<?php
/**
 * FileBird migration driver.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Driver for FileBird.
 *
 * FileBird stores folders in custom table `{prefix}fbv` and assignments
 * in `{prefix}fbv_attachment_folder`.
 *
 * @link https://wordpress.org/plugins/filebird/
 */
final class FileBirdDriver extends AbstractTableDriver {

	/**
	 * Get the driver slug.
	 *
	 * @inheritDoc
	 */
	public static function slug(): string {
		return 'filebird';
	}

	/**
	 * Get the driver display label.
	 *
	 * @inheritDoc
	 */
	public static function label(): string {
		return 'FileBird';
	}

	/**
	 * Get the folder table name.
	 *
	 * @inheritDoc
	 */
	protected function get_folder_table(): string {
		return 'fbv';
	}

	/**
	 * Get the junction table name.
	 *
	 * @inheritDoc
	 */
	protected function get_junction_table(): string {
		return 'fbv_attachment_folder';
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
		return 'attachment_id';
	}

	/**
	 * Get the folder ID junction column.
	 *
	 * @inheritDoc
	 */
	protected function get_junction_folder_column(): string {
		return 'folder_id';
	}
}
