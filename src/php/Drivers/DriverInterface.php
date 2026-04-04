<?php
/**
 * Driver interface for migration sources.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Contract for migration source drivers.
 *
 * Each supported media folder plugin has a corresponding driver that knows
 * how to detect the plugin's data, read its folder structure, and iterate
 * over file-to-folder assignments.
 */
interface DriverInterface {

	/**
	 * Unique driver identifier (e.g. 'enhanced-media-library').
	 *
	 * @return string
	 */
	public static function slug(): string;

	/**
	 * Human-readable plugin name (e.g. 'Enhanced Media Library').
	 *
	 * @return string
	 */
	public static function label(): string;

	/**
	 * Whether the source plugin's data is available for migration.
	 *
	 * Checks if the relevant taxonomy is registered or custom tables exist.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Get the source folder tree.
	 *
	 * Returns a flat array of folder descriptors, each containing:
	 * - id:        int    Source folder ID (term_id or table row ID).
	 * - name:      string Folder name.
	 * - slug:      string Folder slug.
	 * - parent_id: int    Parent folder source ID (0 for root).
	 *
	 * @return array<int, array{id: int, name: string, slug: string, parent_id: int}>
	 */
	public function get_folder_tree(): array;

	/**
	 * Iterate over file-to-folder assignments.
	 *
	 * Yields arrays with:
	 * - attachment_id:   int  WordPress attachment post ID.
	 * - source_folder_id: int Source folder ID matching get_folder_tree() IDs.
	 *
	 * Uses a Generator for memory efficiency on large libraries.
	 *
	 * @return \Generator<int, array{attachment_id: int, source_folder_id: int}>
	 */
	public function get_assignments(): \Generator;

	/**
	 * Get summary statistics for the source.
	 *
	 * @return array{folder_count: int, assignment_count: int}
	 */
	public function get_stats(): array;
}
