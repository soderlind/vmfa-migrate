<?php
/**
 * Insert mock Enhanced Media Library data for testing vmfa-migrate taxonomy support.
 *
 * Creates:
 * 1. media_category taxonomy terms (primary folder taxonomy) + assignments
 * 2. media_tag taxonomy terms (additional custom taxonomy) + assignments
 * 3. wpuxss_eml_taxonomies option (EML settings)
 */

// Register taxonomies temporarily so we can use WP API.
if ( ! taxonomy_exists( 'media_category' ) ) {
	register_taxonomy( 'media_category', 'attachment', [
		'hierarchical' => true,
		'public'       => false,
		'show_ui'      => true,
		'labels'       => [ 'name' => 'Media Categories' ],
	] );
}

if ( ! taxonomy_exists( 'media_tag' ) ) {
	register_taxonomy( 'media_tag', 'attachment', [
		'hierarchical' => false,
		'public'       => false,
		'show_ui'      => true,
		'labels'       => [ 'name' => 'Media Tags' ],
	] );
}

// --- Phase 1: Create media_category terms (hierarchical) ---
echo "Creating media_category terms...\n";

$photos = wp_insert_term( 'Photos', 'media_category' );
$photos_id = is_wp_error( $photos ) ? $photos->get_error_data() : $photos['term_id'];
echo "  Photos: term_id=$photos_id\n";

$landscapes = wp_insert_term( 'Landscapes', 'media_category', [ 'parent' => $photos_id ] );
$landscapes_id = is_wp_error( $landscapes ) ? $landscapes->get_error_data() : $landscapes['term_id'];
echo "  Landscapes: term_id=$landscapes_id\n";

$portraits = wp_insert_term( 'Portraits', 'media_category', [ 'parent' => $photos_id ] );
$portraits_id = is_wp_error( $portraits ) ? $portraits->get_error_data() : $portraits['term_id'];
echo "  Portraits: term_id=$portraits_id\n";

$documents = wp_insert_term( 'Documents', 'media_category' );
$documents_id = is_wp_error( $documents ) ? $documents->get_error_data() : $documents['term_id'];
echo "  Documents: term_id=$documents_id\n";

$invoices = wp_insert_term( 'Invoices', 'media_category', [ 'parent' => $documents_id ] );
$invoices_id = is_wp_error( $invoices ) ? $invoices->get_error_data() : $invoices['term_id'];
echo "  Invoices: term_id=$invoices_id\n";

// --- Phase 2: Create media_tag terms (flat) ---
echo "\nCreating media_tag terms...\n";

$tag_nature = wp_insert_term( 'nature', 'media_tag' );
$tag_nature_id = is_wp_error( $tag_nature ) ? $tag_nature->get_error_data() : $tag_nature['term_id'];
echo "  nature: term_id=$tag_nature_id\n";

$tag_work = wp_insert_term( 'work', 'media_tag' );
$tag_work_id = is_wp_error( $tag_work ) ? $tag_work->get_error_data() : $tag_work['term_id'];
echo "  work: term_id=$tag_work_id\n";

$tag_featured = wp_insert_term( 'featured', 'media_tag' );
$tag_featured_id = is_wp_error( $tag_featured ) ? $tag_featured->get_error_data() : $tag_featured['term_id'];
echo "  featured: term_id=$tag_featured_id\n";

$tag_archive = wp_insert_term( 'archive', 'media_tag' );
$tag_archive_id = is_wp_error( $tag_archive ) ? $tag_archive->get_error_data() : $tag_archive['term_id'];
echo "  archive: term_id=$tag_archive_id\n";

// --- Phase 3: Get some attachment IDs ---
$attachments = get_posts( [
	'post_type'      => 'attachment',
	'posts_per_page' => 12,
	'orderby'        => 'ID',
	'order'          => 'DESC',
	'fields'         => 'ids',
] );

if ( count( $attachments ) < 6 ) {
	echo "ERROR: Need at least 6 attachments, found " . count( $attachments ) . "\n";
	exit( 1 );
}

echo "\nUsing attachments: " . implode( ', ', array_slice( $attachments, 0, 12 ) ) . "\n";

// --- Phase 4: Assign attachments to media_category ---
echo "\nAssigning attachments to media_category...\n";

wp_set_object_terms( $attachments[0], [ $landscapes_id ], 'media_category' );
wp_set_object_terms( $attachments[1], [ $landscapes_id ], 'media_category' );
wp_set_object_terms( $attachments[2], [ $portraits_id ], 'media_category' );
wp_set_object_terms( $attachments[3], [ $portraits_id ], 'media_category' );
wp_set_object_terms( $attachments[4], [ $documents_id ], 'media_category' );
wp_set_object_terms( $attachments[5], [ $invoices_id ], 'media_category' );

if ( isset( $attachments[6] ) ) {
	wp_set_object_terms( $attachments[6], [ $photos_id ], 'media_category' );
}
if ( isset( $attachments[7] ) ) {
	wp_set_object_terms( $attachments[7], [ $invoices_id ], 'media_category' );
}

echo "  Assigned 6-8 attachments to media_category\n";

// --- Phase 5: Assign attachments to media_tag ---
echo "\nAssigning attachments to media_tag...\n";

wp_set_object_terms( $attachments[0], [ $tag_nature_id, $tag_featured_id ], 'media_tag' );
wp_set_object_terms( $attachments[1], [ $tag_nature_id ], 'media_tag' );
wp_set_object_terms( $attachments[2], [ $tag_featured_id ], 'media_tag' );
wp_set_object_terms( $attachments[3], [ $tag_work_id ], 'media_tag' );
wp_set_object_terms( $attachments[4], [ $tag_work_id, $tag_archive_id ], 'media_tag' );
wp_set_object_terms( $attachments[5], [ $tag_archive_id ], 'media_tag' );

echo "  Assigned 6 attachments to media_tag (8 total assignments)\n";

// --- Phase 6: Create EML settings option ---
echo "\nCreating wpuxss_eml_taxonomies option...\n";

$eml_taxonomies = [
	'media_category' => [
		'hierarchical' => true,
		'labels'       => [
			'name'          => 'Media Categories',
			'singular_name' => 'Media Category',
		],
		'show_admin_column' => true,
		'show_in_nav_menus' => true,
		'assigned'          => true,
		'eml_media'         => true,
	],
	'media_tag' => [
		'hierarchical' => false,
		'labels'       => [
			'name'          => 'Media Tags',
			'singular_name' => 'Media Tag',
		],
		'show_admin_column' => true,
		'show_in_nav_menus' => true,
		'assigned'          => true,
		'eml_media'         => true,
	],
];

update_option( 'wpuxss_eml_taxonomies', $eml_taxonomies );
echo "  wpuxss_eml_taxonomies option saved\n";

// --- Summary ---
echo "\n=== Mock EML Data Summary ===\n";
echo "media_category: 5 terms (3 root, 2 children), 6-8 assignments\n";
echo "media_tag: 4 terms (flat), 8 assignments\n";
echo "EML settings option created with both taxonomies\n";
echo "Done!\n";
