=== Virtual Media Folders - Migrate ===
Contributors: suspended
Tags: media, folders, migration, import
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.3
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Migrate media folders from Enhanced Media Library, FileBird, Real Media Library, and more into Virtual Media Folders.

== Description ==

Migration add-on for Virtual Media Folders. Detect and import folder structures and file assignments from 7 popular media folder plugins.

Supports:

* Enhanced Media Library
* FileBird
* Real Media Library
* HappyFiles
* WP Media Folder (JoomUnited)
* Media Library Assistant
* CatFolders

Works even after the source plugin has been deactivated — data is read directly from the database.

== Taxonomy Migration ==

Enhanced Media Library and Media Library Assistant support additional taxonomies
(e.g. media tags) beyond their primary folder taxonomy. When you enable
"Include taxonomies", these are migrated as standard WordPress taxonomies on
each attachment — the terms and assignments are preserved in the database.

Virtual Media Folders does not display these taxonomies in its sidebar. They are
stored so the data is not lost. You can access them with native WordPress hooks.

**Register the taxonomy** (e.g. in your theme's `functions.php` or a custom plugin):

    add_action( 'init', function () {
        register_taxonomy( 'media_tag', 'attachment', [
            'label'        => __( 'Media Tags' ),
            'public'       => true,
            'hierarchical' => false,
            'show_ui'      => true,
            'show_in_rest' => true,
        ] );
    } );

**Get terms for an attachment:**

    $terms = get_the_terms( $attachment_id, 'media_tag' );
    if ( $terms && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            echo $term->name;
        }
    }

**Query attachments by term:**

    $attachments = get_posts( [
        'post_type'   => 'attachment',
        'post_status' => 'inherit',
        'tax_query'   => [
            [
                'taxonomy' => 'media_tag',
                'field'    => 'slug',
                'terms'    => 'nature',
            ],
        ],
    ] );

Replace `media_tag` with the actual taxonomy slug shown during migration
(e.g. `attachment_tag` for Media Library Assistant).

== Installation ==

1. Install and activate Virtual Media Folders.
2. Upload `vmfa-migrate` to the `/wp-content/plugins/` directory.
3. Activate the plugin.
4. Go to Media → Folder Settings → Migration.

== Changelog ==

= 0.3.0 =
* Added: Taxonomy migration for Enhanced Media Library and Media Library Assistant.
* Added: `--include-taxonomies` WP-CLI flag and UI checkbox to opt in.
* Added: Preview shows available taxonomies with term and assignment counts.
* Added: Documentation with examples for accessing migrated terms via WordPress hooks.
* Improved: Preview panel UX — taxonomy checkbox co-located with taxonomy table.
* Improved: Progress panel — unified results table with section headers.

= 0.2.0 =
* Improved: Conflict strategy selector — clearer label, descriptive option text, and help text explaining each strategy.

= 0.1.0 =
* Initial release.
* Detection of 7 media folder plugins: Enhanced Media Library, FileBird, Real Media Library, HappyFiles, WP Media Folder, Media Library Assistant, CatFolders.
* Taxonomy-based drivers with deactivated-plugin fallback (direct DB queries).
* Custom-table drivers for FileBird, Real Media Library, CatFolders.
* Batched migration via Action Scheduler.
* Conflict strategies: skip, merge, overwrite.
* Admin UI (React) with source detection, folder preview, migration controls, and progress tracking.
* WP-CLI commands: list-sources, preview, run.
* REST API endpoints for programmatic access.
* Extensible driver system via vmfa_migrate_drivers filter.
