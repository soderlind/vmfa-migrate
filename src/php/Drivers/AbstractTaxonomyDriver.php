<?php
/**
 * Abstract driver for taxonomy-based media folder plugins.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for plugins that store folders as WordPress taxonomy terms.
 *
 * Works even when the source plugin is deactivated by falling back to direct
 * database queries on wp_term_taxonomy when the taxonomy is not registered.
 */
abstract class AbstractTaxonomyDriver implements DriverInterface {

	/**
	 * The source taxonomy slug (e.g. 'media_category').
	 *
	 * @return string
	 */
	abstract protected function get_taxonomy(): string;

	/**
	 * Check if the source taxonomy data is available.
	 *
	 * @inheritDoc
	 */
	public function is_available(): bool {
		if ( taxonomy_exists( $this->get_taxonomy() ) ) {
			return true;
		}

		// Fallback: check if term_taxonomy rows exist even if plugin is deactivated.
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
				$this->get_taxonomy()
			)
		);

		return $count > 0;
	}

	/**
	 * Get the folder tree from the taxonomy.
	 *
	 * @inheritDoc
	 */
	public function get_folder_tree(): array {
		if ( taxonomy_exists( $this->get_taxonomy() ) ) {
			return $this->get_folder_tree_via_api();
		}

		return $this->get_folder_tree_via_db();
	}

	/**
	 * Get attachment-folder assignments.
	 *
	 * @inheritDoc
	 */
	public function get_assignments(): \Generator {
		global $wpdb;

		$taxonomy = $this->get_taxonomy();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tr.object_id AS attachment_id, tt.term_id AS source_folder_id
				 FROM {$wpdb->term_relationships} tr
				 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				 WHERE tt.taxonomy = %s
				 ORDER BY tr.object_id ASC",
				$taxonomy
			),
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

		$taxonomy = $this->get_taxonomy();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$folder_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
				$taxonomy
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$assignment_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
				 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				 WHERE tt.taxonomy = %s",
				$taxonomy
			)
		);

		return [
			'folder_count'     => $folder_count,
			'assignment_count' => $assignment_count,
		];
	}

	/**
	 * Get folder tree using the WordPress taxonomy API.
	 *
	 * @return array<int, array{id: int, name: string, slug: string, parent_id: int}>
	 */
	private function get_folder_tree_via_api(): array {
		return $this->get_terms_for_taxonomy_via_api( $this->get_taxonomy() );
	}

	/**
	 * Get folder tree via direct database queries (when taxonomy is not registered).
	 *
	 * @return array<int, array{id: int, name: string, slug: string, parent_id: int}>
	 */
	private function get_folder_tree_via_db(): array {
		return $this->get_terms_for_taxonomy_via_db( $this->get_taxonomy() );
	}

	/**
	 * Check if a given taxonomy has data in the database.
	 *
	 * Works even when the taxonomy is not registered (plugin deactivated).
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return bool
	 */
	protected function taxonomy_has_data( string $taxonomy ): bool {
		if ( taxonomy_exists( $taxonomy ) ) {
			return true;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
				$taxonomy
			)
		);

		return $count > 0;
	}

	/**
	 * Get terms for any taxonomy, with API or DB fallback.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int, array{id: int, name: string, slug: string, parent_id: int}>
	 */
	protected function get_terms_for_taxonomy( string $taxonomy ): array {
		if ( taxonomy_exists( $taxonomy ) ) {
			return $this->get_terms_for_taxonomy_via_api( $taxonomy );
		}

		return $this->get_terms_for_taxonomy_via_db( $taxonomy );
	}

	/**
	 * Get terms for a taxonomy via WP API.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int, array{id: int, name: string, slug: string, parent_id: int}>
	 */
	protected function get_terms_for_taxonomy_via_api( string $taxonomy ): array {
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		$result = [];
		foreach ( $terms as $term ) {
			$result[] = [
				'id'        => $term->term_id,
				'name'      => $term->name,
				'slug'      => $term->slug,
				'parent_id' => $term->parent,
			];
		}

		return $result;
	}

	/**
	 * Get terms for a taxonomy via direct DB queries.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int, array{id: int, name: string, slug: string, parent_id: int}>
	 */
	protected function get_terms_for_taxonomy_via_db( string $taxonomy ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.term_id, t.name, t.slug, tt.parent
				 FROM {$wpdb->terms} t
				 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
				 WHERE tt.taxonomy = %s
				 ORDER BY t.name ASC",
				$taxonomy
			),
			ARRAY_A
		);

		$result = [];
		foreach ( $rows as $row ) {
			$result[] = [
				'id'        => (int) $row['term_id'],
				'name'      => $row['name'],
				'slug'      => $row['slug'],
				'parent_id' => (int) $row['parent'],
			];
		}

		return $result;
	}

	/**
	 * Count terms for a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return int
	 */
	protected function count_taxonomy_terms( string $taxonomy ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
				$taxonomy
			)
		);
	}

	/**
	 * Count attachment-term assignments for a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return int
	 */
	protected function count_taxonomy_assignments( string $taxonomy ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
				 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				 WHERE tt.taxonomy = %s",
				$taxonomy
			)
		);
	}

	/**
	 * Iterate over attachment-term assignments for a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return \Generator<int, array{attachment_id: int, term_id: int}>
	 */
	protected function iterate_taxonomy_assignments( string $taxonomy ): \Generator {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tr.object_id AS attachment_id, tt.term_id
				 FROM {$wpdb->term_relationships} tr
				 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				 WHERE tt.taxonomy = %s
				 ORDER BY tr.object_id ASC",
				$taxonomy
			),
			ARRAY_A
		);

		foreach ( $results as $row ) {
			yield [
				'attachment_id' => (int) $row['attachment_id'],
				'term_id'       => (int) $row['term_id'],
			];
		}
	}
}
