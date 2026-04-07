<?php
/**
 * Insert mock Media Library Assistant data for testing vmfa-migrate taxonomy support.
 *
 * Creates:
 * 1. attachment_category taxonomy terms (primary folder taxonomy) + assignments
 * 2. attachment_tag taxonomy terms (additional taxonomy) + assignments
 * 3. mla_taxonomy_support option (MLA settings)
 */

// Register taxonomies temporarily.
if ( ! taxonomy_exists( 'attachment_category' ) ) {
	register_taxonomy( 'attachment_category', 'attachment', [
		'hierarchical' => true,
		'public'       => false,
		'show_ui'      => true,
		'labels'       => [ 'name' => 'Attachment Categories' ],
	] );
}

if ( ! taxonomy_exists( 'attachment_tag' ) ) {
	register_taxonomy( 'attachment_tag', 'attachment', [
		'hierarchical' => false,
		'public'       => false,
		'show_ui'      => true,
		'labels'       => [ 'name' => 'Attachment Tags' ],
	] );
}

// --- Phase 1: Create attachment_category terms (hierarchical) ---
echo "Creating attachment_category terms...\n";

$projects = wp_insert_term( 'Projects', 'attachment_category' );
$projects_id = is_wp_error( $projects ) ? $projects->get_error_data() : $projects['term_id'];
echo "  Projects: term_id=$projects_id\n";

$client_a = wp_insert_term( 'Client A', 'attachment_category', [ 'parent' => $projects_id ] );
$client_a_id = is_wp_error( $client_a ) ? $client_a->get_error_data() : $client_a['term_id'];
echo "  Client A: term_id=$client_a_id\n";

$client_b = wp_insert_term( 'Client B', 'attachment_category', [ 'parent' => $projects_id ] );
$client_b_id = is_wp_error( $client_b ) ? $client_b->get_error_data() : $client_b['term_id'];
echo "  Client B: term_id=$client_b_id\n";

$resources = wp_insert_term( 'Resources', 'attachment_category' );
$resources_id = is_wp_error( $resources ) ? $resources->get_error_data() : $resources['term_id'];
echo "  Resources: term_id=$resources_id\n";

// --- Phase 2: Create attachment_tag terms (flat) ---
echo "\nCreating attachment_tag terms...\n";

$tag_logo = wp_insert_term( 'logo', 'attachment_tag' );
$tag_logo_id = is_wp_error( $tag_logo ) ? $tag_logo->get_error_data() : $tag_logo['term_id'];
echo "  logo: term_id=$tag_logo_id\n";

$tag_banner = wp_insert_term( 'banner', 'attachment_tag' );
$tag_banner_id = is_wp_error( $tag_banner ) ? $tag_banner->get_error_data() : $tag_banner['term_id'];
echo "  banner: term_id=$tag_banner_id\n";

$tag_draft = wp_insert_term( 'draft', 'attachment_tag' );
$tag_draft_id = is_wp_error( $tag_draft ) ? $tag_draft->get_error_data() : $tag_draft['term_id'];
echo "  draft: term_id=$tag_draft_id\n";

// --- Phase 3: Get attachment IDs (use different ones than EML) ---
$attachments = get_posts( [
	'post_type'      => 'attachment',
	'posts_per_page' => 12,
	'orderby'        => 'ID',
	'order'          => 'ASC',
	'fields'         => 'ids',
] );

if ( count( $attachments ) < 6 ) {
	echo "ERROR: Need at least 6 attachments, found " . count( $attachments ) . "\n";
	exit( 1 );
}

echo "\nUsing attachments: " . implode( ', ', array_slice( $attachments, 0, 8 ) ) . "\n";

// --- Phase 4: Assign attachments to attachment_category ---
echo "\nAssigning attachments to attachment_category...\n";

wp_set_object_terms( $attachments[0], [ $client_a_id ], 'attachment_category' );
wp_set_object_terms( $attachments[1], [ $client_a_id ], 'attachment_category' );
wp_set_object_terms( $attachments[2], [ $client_b_id ], 'attachment_category' );
wp_set_object_terms( $attachments[3], [ $resources_id ], 'attachment_category' );
wp_set_object_terms( $attachments[4], [ $resources_id ], 'attachment_category' );
wp_set_object_terms( $attachments[5], [ $projects_id ], 'attachment_category' );

echo "  Assigned 6 attachments to attachment_category\n";

// --- Phase 5: Assign attachments to attachment_tag ---
echo "\nAssigning attachments to attachment_tag...\n";

wp_set_object_terms( $attachments[0], [ $tag_logo_id ], 'attachment_tag' );
wp_set_object_terms( $attachments[1], [ $tag_banner_id, $tag_logo_id ], 'attachment_tag' );
wp_set_object_terms( $attachments[2], [ $tag_banner_id ], 'attachment_tag' );
wp_set_object_terms( $attachments[3], [ $tag_draft_id ], 'attachment_tag' );
wp_set_object_terms( $attachments[4], [ $tag_draft_id ], 'attachment_tag' );

echo "  Assigned 5 attachments to attachment_tag (6 total assignments)\n";

// --- Phase 6: Create MLA settings option ---
echo "\nCreating mla_taxonomy_support option...\n";

$mla_support = [
	'tax_support' => [
		'attachment_category' => true,
		'attachment_tag'      => true,
	],
];

update_option( 'mla_taxonomy_support', $mla_support );
echo "  mla_taxonomy_support option saved\n";

// --- Summary ---
echo "\n=== Mock MLA Data Summary ===\n";
echo "attachment_category: 4 terms (2 root, 2 children), 6 assignments\n";
echo "attachment_tag: 3 terms (flat), 6 assignments\n";
echo "MLA settings option created\n";
echo "Done!\n";
