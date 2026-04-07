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
 * It also supports unlimited custom taxonomies for media, stored in the
 * 'wpuxss_eml_taxonomies' option.
 *
 * @link https://wordpress.org/plugins/enhanced-media-library/
 */
final class EnhancedMediaLibraryDriver extends AbstractTaxonomyDriver implements TaxonomyAwareDriverInterface {

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

	/**
	 * Get additional taxonomies managed by EML.
	 *
	 * Reads the 'wpuxss_eml_taxonomies' option which stores all EML-managed
	 * taxonomies. Filters out the primary folder taxonomy (media_category).
	 *
	 * @inheritDoc
	 */
	public function get_additional_taxonomies(): array {
		$eml_taxonomies = get_option( 'wpuxss_eml_taxonomies', [] );

		if ( ! is_array( $eml_taxonomies ) || empty( $eml_taxonomies ) ) {
			return [];
		}

		$primary    = $this->get_taxonomy();
		$additional = [];

		foreach ( $eml_taxonomies as $slug => $config ) {
			// Skip the primary folder taxonomy.
			if ( $slug === $primary ) {
				continue;
			}

			// Skip if no data exists for this taxonomy.
			if ( ! $this->taxonomy_has_data( $slug ) ) {
				continue;
			}

			$label        = $config['labels']['name'] ?? $slug;
			$hierarchical = ! empty( $config['hierarchical'] );

			$additional[] = [
				'slug'         => $slug,
				'label'        => $label,
				'hierarchical' => $hierarchical,
				'term_count'   => $this->count_taxonomy_terms( $slug ),
				'assign_count' => $this->count_taxonomy_assignments( $slug ),
			];
		}

		return $additional;
	}

	/**
	 * Get terms for an additional taxonomy.
	 *
	 * @inheritDoc
	 */
	public function get_taxonomy_terms( string $taxonomy ): array {
		return $this->get_terms_for_taxonomy( $taxonomy );
	}

	/**
	 * Get attachment-to-term assignments for an additional taxonomy.
	 *
	 * @inheritDoc
	 */
	public function get_taxonomy_assignments( string $taxonomy ): \Generator {
		yield from $this->iterate_taxonomy_assignments( $taxonomy );
	}
}
