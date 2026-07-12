# Changelog

All notable changes to this project will be documented in this file.

## [0.3.2] - 2026-07-12

### Security

- Resolved the majority of Dependabot alerts by updating build/test dependencies (`npm audit fix`, `@wordpress/scripts` 31 → 32, `@wordpress/components` 32 → 36). Remaining alerts are dev-only transitive dependencies pinned by `@wordpress/scripts`.

### Changed

- Added grouped `.github/dependabot.yml` config (npm/composer/github-actions) to consolidate future dependency update PRs.
- Synced `VMFA_MIGRATE_VERSION` constant with the plugin header version.

## [0.3.1] - 2026-04-30

### Changed

- Updated "Tested up to" to WordPress 7.0

## [0.3.0] - 2026-04-08

### Added

- Taxonomy migration for Enhanced Media Library and Media Library Assistant.
- New `TaxonomyAwareDriverInterface` for drivers with additional taxonomies.
- `--include-taxonomies` WP-CLI flag and UI checkbox to opt in.
- Preview shows available taxonomies with term and assignment counts.
- Progress view shows taxonomy migration results alongside folder stats.
- Documentation with examples for accessing migrated terms via WordPress hooks.

### Improved

- Preview panel: taxonomy checkbox co-located with taxonomy table for better UX.
- Progress panel: unified results table with section headers.

## [0.2.0] - 2026-04-04

### Improved

- Conflict strategy selector: clearer label, descriptive option text, and help text explaining each strategy.

## [0.1.0] - 2026-04-04

### Added

- Initial release.
- Detection of 7 media folder plugins: Enhanced Media Library, FileBird, Real Media Library, HappyFiles, WP Media Folder, Media Library Assistant, CatFolders.
- Taxonomy-based drivers with deactivated-plugin fallback (direct DB queries).
- Custom-table drivers for FileBird, Real Media Library, CatFolders.
- Batched migration via Action Scheduler.
- Conflict strategies: skip, merge, overwrite.
- Admin UI (React) with source detection, folder preview, migration controls, and progress tracking.
- WP-CLI commands: `list-sources`, `preview`, `run`.
- REST API endpoints for programmatic access.
- Extensible driver system via `vmfa_migrate_drivers` filter.
