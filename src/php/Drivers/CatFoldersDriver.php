<?php
/**
 * CatFolders migration driver.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Driver for CatFolders.
 *
 * CatFolders stores folders in custom table `{prefix}catfolders` and assignments
 * in `{prefix}catfolders_posts`.
 *
 * @link https://wordpress.org/plugins/catfolders/
 */
final class CatFoldersDriver extends AbstractTableDriver {

	/**
	 * Get the driver slug.
	 *
	 * @inheritDoc
	 */
	public static function slug(): string {
		return 'catfolders';
	}

	/**
	 * Get the driver display label.
	 *
	 * @inheritDoc
	 */
	public static function label(): string {
		return 'CatFolders';
	}

	/**
	 * Get the folder table name.
	 *
	 * @inheritDoc
	 */
	protected function get_folder_table(): string {
		return 'catfolders';
	}

	/**
	 * Get the junction table name.
	 *
	 * @inheritDoc
	 */
	protected function get_junction_table(): string {
		return 'catfolders_posts';
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
		return 'post_id';
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
