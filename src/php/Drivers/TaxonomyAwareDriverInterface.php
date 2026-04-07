<?php
/**
 * Interface for drivers that support additional taxonomy migration.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Optional contract for drivers whose source plugins support multiple
 * taxonomies beyond the primary folder taxonomy.
 *
 * Drivers that implement this interface expose additional taxonomies
 * (e.g. EML's custom taxonomies, MLA's attachment_tag) that can be
 * migrated alongside the main folder structure.
 */
interface TaxonomyAwareDriverInterface extends DriverInterface {

	/**
	 * Get additional taxonomies from the source plugin (excluding the
	 * primary folder taxonomy already handled by get_folder_tree()).
	 *
	 * Returns an array of taxonomy descriptors:
	 * - slug:         string  Taxonomy slug (e.g. 'media_tag').
	 * - label:        string  Human-readable label.
	 * - hierarchical: bool    Whether the taxonomy is hierarchical.
	 * - term_count:   int     Number of terms.
	 * - assign_count: int     Number of attachment-term assignments.
	 *
	 * @return array<int, array{slug: string, label: string, hierarchical: bool, term_count: int, assign_count: int}>
	 */
	public function get_additional_taxonomies(): array;

	/**
	 * Get terms for an additional taxonomy.
	 *
	 * Returns a flat array of term descriptors:
	 * - id:        int    Term ID.
	 * - name:      string Term name.
	 * - slug:      string Term slug.
	 * - parent_id: int    Parent term ID (0 for root).
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int, array{id: int, name: string, slug: string, parent_id: int}>
	 */
	public function get_taxonomy_terms( string $taxonomy ): array;

	/**
	 * Get attachment-to-term assignments for an additional taxonomy.
	 *
	 * Yields arrays with:
	 * - attachment_id: int  WordPress attachment post ID.
	 * - term_id:       int  Source term ID matching get_taxonomy_terms() IDs.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return \Generator<int, array{attachment_id: int, term_id: int}>
	 */
	public function get_taxonomy_assignments( string $taxonomy ): \Generator;
}
