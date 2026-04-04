=== Virtual Media Folders - Migrate ===
Contributors: suspended
Tags: media, folders, migration, import
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.3
Stable tag: 0.2.0
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

== Installation ==

1. Install and activate Virtual Media Folders.
2. Upload `vmfa-migrate` to the `/wp-content/plugins/` directory.
3. Activate the plugin.
4. Go to Media → Folder Settings → Migration.

== Changelog ==

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
* WP-CLI commands: list_sources, preview, run.
* REST API endpoints for programmatic access.
* Extensible driver system via vmfa_migrate_drivers filter.
