<?php
/**
 * Abstract driver for custom-table-based media folder plugins.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for plugins that store folders in custom database tables.
 *
 * Subclasses provide table names and column mappings via abstract getters.
 */
abstract class AbstractTableDriver implements DriverInterface {

	/**
	 * The folder table name without prefix (e.g. 'fbv').
	 *
	 * @return string
	 */
	abstract protected function get_folder_table(): string;

	/**
	 * The junction table name without prefix (e.g. 'fbv_attachment_folder').
	 *
	 * @return string
	 */
	abstract protected function get_junction_table(): string;

	/**
	 * Column name for folder ID in the folder table.
	 *
	 * @return string
	 */
	abstract protected function get_folder_id_column(): string;

	/**
	 * Column name for folder name in the folder table.
	 *
	 * @return string
	 */
	abstract protected function get_folder_name_column(): string;

	/**
	 * Column name for parent folder ID in the folder table.
	 *
	 * @return string
	 */
	abstract protected function get_folder_parent_column(): string;

	/**
	 * Column name for attachment ID in the junction table.
	 *
	 * @return string
	 */
	abstract protected function get_junction_attachment_column(): string;

	/**
	 * Column name for folder ID in the junction table.
	 *
	 * @return string
	 */
	abstract protected function get_junction_folder_column(): string;

	/**
	 * Get the prefixed folder table name.
	 *
	 * @return string
	 */
	protected function folder_table(): string {
		global $wpdb;
		return $wpdb->prefix . $this->get_folder_table();
	}

	/**
	 * Get the prefixed junction table name.
	 *
	 * @return string
	 */
	protected function junction_table(): string {
		global $wpdb;
		return $wpdb->prefix . $this->get_junction_table();
	}

	/**
	 * Check if the source data is available.
	 *
	 * @inheritDoc
	 */
	public function is_available(): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$this->folder_table()
			)
		);

		return null !== $table_exists;
	}

	/**
	 * Get the folder tree from the source.
	 *
	 * @inheritDoc
	 */
	public function get_folder_tree(): array {
		global $wpdb;

		$table      = $this->folder_table();
		$id_col     = $this->get_folder_id_column();
		$name_col   = $this->get_folder_name_column();
		$parent_col = $this->get_folder_parent_column();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Column/table names from trusted driver config.
			"SELECT `{$id_col}` AS id, `{$name_col}` AS name, `{$parent_col}` AS parent_id FROM `{$table}` ORDER BY `{$name_col}` ASC",
			ARRAY_A
		);

		if ( ! $rows ) {
			return [];
		}

		$folders = [];
		foreach ( $rows as $row ) {
			$folders[] = [
				'id'        => (int) $row['id'],
				'name'      => $row['name'],
				'slug'      => sanitize_title( $row['name'] ),
				'parent_id' => (int) $row['parent_id'],
			];
		}

		return $folders;
	}

	/**
	 * Get attachment-folder assignments.
	 *
	 * @inheritDoc
	 */
	public function get_assignments(): \Generator {
		global $wpdb;

		$table          = $this->junction_table();
		$attachment_col = $this->get_junction_attachment_column();
		$folder_col     = $this->get_junction_folder_column();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Column/table names from trusted driver config.
			"SELECT `{$attachment_col}` AS attachment_id, `{$folder_col}` AS source_folder_id FROM `{$table}` ORDER BY `{$attachment_col}` ASC",
			ARRAY_A
		);

		foreach ( $results as $row ) {
			yield [
				'attachment_id'    => (int) $row['attachment_id'],
				'source_folder_id' => (int) $row['source_folder_id'],
			];
		}
	}

	/**
	 * Get source statistics.
	 *
	 * @inheritDoc
	 */
	public function get_stats(): array {
		global $wpdb;

		$folder_table   = $this->folder_table();
		$junction_table = $this->junction_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$folder_count = (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from trusted driver config.
			"SELECT COUNT(*) FROM `{$folder_table}`"
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$assignment_count = (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from trusted driver config.
			"SELECT COUNT(*) FROM `{$junction_table}`"
		);

		return [
			'folder_count'     => $folder_count,
			'assignment_count' => $assignment_count,
		];
	}
}
