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
 * It also supports 'attachment_tag' and can enable any WordPress taxonomy
 * (including Categories, Tags, and custom) for the attachment post type.
 *
 * @link https://wordpress.org/plugins/media-library-assistant/
 */
final class MediaLibraryAssistantDriver extends AbstractTaxonomyDriver implements TaxonomyAwareDriverInterface {

	/**
	 * MLA's built-in taxonomies (beyond the primary folder taxonomy).
	 *
	 * @var array<string, string>
	 */
	private const MLA_BUILTIN_TAXONOMIES = [
		'attachment_tag' => 'Attachment Tags',
	];

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

	/**
	 * Get additional taxonomies managed by MLA.
	 *
	 * Checks for MLA's built-in attachment_tag taxonomy and any WordPress
	 * taxonomies MLA has enabled for attachments via its taxonomy support
	 * option.
	 *
	 * @inheritDoc
	 */
	public function get_additional_taxonomies(): array {
		$primary    = $this->get_taxonomy();
		$additional = [];

		// Check MLA's built-in attachment_tag.
		foreach ( self::MLA_BUILTIN_TAXONOMIES as $slug => $label ) {
			if ( ! $this->taxonomy_has_data( $slug ) ) {
				continue;
			}

			$hierarchical = false;
			if ( taxonomy_exists( $slug ) ) {
				$tax_obj      = get_taxonomy( $slug );
				$hierarchical = $tax_obj ? $tax_obj->hierarchical : false;
			}

			$additional[] = [
				'slug'         => $slug,
				'label'        => $label,
				'hierarchical' => $hierarchical,
				'term_count'   => $this->count_taxonomy_terms( $slug ),
				'assign_count' => $this->count_taxonomy_assignments( $slug ),
			];
		}

		// Check MLA taxonomy support option for additional enabled taxonomies.
		$mla_support = get_option( 'mla_taxonomy_support', [] );

		if ( is_array( $mla_support ) && ! empty( $mla_support['tax_support'] ) ) {
			foreach ( $mla_support['tax_support'] as $slug => $enabled ) {
				// Skip primary and already-listed taxonomies.
				if ( $slug === $primary || isset( self::MLA_BUILTIN_TAXONOMIES[ $slug ] ) ) {
					continue;
				}

				// Skip disabled taxonomies.
				if ( empty( $enabled ) ) {
					continue;
				}

				if ( ! $this->taxonomy_has_data( $slug ) ) {
					continue;
				}

				$label        = $slug;
				$hierarchical = false;

				if ( taxonomy_exists( $slug ) ) {
					$tax_obj = get_taxonomy( $slug );
					if ( $tax_obj ) {
						$label        = $tax_obj->labels->name ?? $slug;
						$hierarchical = $tax_obj->hierarchical;
					}
				}

				$additional[] = [
					'slug'         => $slug,
					'label'        => $label,
					'hierarchical' => $hierarchical,
					'term_count'   => $this->count_taxonomy_terms( $slug ),
					'assign_count' => $this->count_taxonomy_assignments( $slug ),
				];
			}
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
