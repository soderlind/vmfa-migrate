# Plan: vmfa-migrate — Media Plugin Migration Add-on

## TL;DR

Create a VMF add-on (`vmfa-migrate`) that detects installed media folder plugins, reads their folder/assignment data, and migrates it into VMF's `vmfo_folder` taxonomy. Supports 7 popular plugins across two storage paradigms (taxonomy-based and custom-table-based). Uses batched processing via Action Scheduler for large libraries.

**Requirements:** PHP 8.3+, WP 6.8+, VMF 2.0.0+. Follows addon-development.md patterns exactly. If any existing plugin/add-on must be modified, create a branch first.

---

## Source Plugin Analysis

### Taxonomy-based (remap terms via `wp_term_taxonomy`)

| Plugin                         | Taxonomy Slug        | Hierarchical | Installs   |
| ------------------------------ | -------------------- | ------------ | ---------- |
| Enhanced Media Library         | `media_category`     | Yes          | 70k+       |
| HappyFiles                     | `happyfiles_category`| Yes          | N/A (closed)|
| WP Media Folder (JoomUnited)   | `wpmf-category`      | Yes          | Commercial |
| Media Library Assistant        | `attachment_category` | Yes          | 70k+       |

### Custom-table-based (read plugin tables)

| Plugin              | Folder Table                | Junction Table                    | Installs |
| ------------------- | --------------------------- | --------------------------------- | -------- |
| FileBird            | `{prefix}fbv`               | `{prefix}fbv_attachment_folder`   | 200k+    |
| Real Media Library  | `{prefix}realmedialibrary`  | `{prefix}realmedialibrary_posts`  | 100k+    |
| CatFolders          | `{prefix}catfolders`        | `{prefix}catfolders_posts`        | 6k+      |

---

## Architecture

Each source plugin is a **Driver** class implementing a common interface. Drivers handle detection, folder tree reading, and attachment-to-folder mapping. A **MigrationService** orchestrates the process using Action Scheduler for batched work.

---

## Checklist

### Phase 1: Scaffold & Core Infrastructure

- [x] **1.1** Scaffold `vmfa-migrate.php` — plugin header (`Requires Plugins: virtual-media-folders`, `Requires PHP: 8.3`, `Requires at least: 6.8`), constants, PSR-4 autoload, Action Scheduler early load, GitHub updater, `plugins_loaded` init at priority 20
- [x] **1.2** `composer.json` — PSR-4 `VmfaMigrate\` → `src/php/`, require `php >=8.3`, `woocommerce/action-scheduler`
- [x] **1.3** `composer install` — vendor directory with dependencies
- [x] **1.4** `package.json` — `@wordpress/scripts` build, vitest, eslint, i18n scripts
- [x] **1.5** Config files — `phpcs.xml`, `phpunit.xml.dist`, `vitest.config.js`, `i18n-map.json`
- [x] **1.6** Boilerplate files — `class-github-updater.php`, `uninstall.php`, `README.md`, `readme.txt`, `CHANGELOG.md`
- [x] **1.7** `Plugin.php` — extends `AbstractPlugin`, wires `DetectorService`, `MigrationService`, `SettingsTab`, REST, CLI
- [x] **1.8** `DriverInterface.php` — contract: `slug()`, `label()`, `is_available()`, `get_folder_tree()`, `get_assignments()` (Generator), `get_stats()`
- [x] **1.9** `AbstractTaxonomyDriver.php` — shared taxonomy logic using `get_terms()` + direct DB fallback when source plugin is deactivated
- [x] **1.10** `AbstractTableDriver.php` — shared `$wpdb->prepare()` queries, table/column mapping via abstract getters, table-existence detection

### Phase 2: Individual Drivers

- [x] **2.1** `EnhancedMediaLibraryDriver.php` — taxonomy `media_category`
- [x] **2.2** `HappyFilesDriver.php` — taxonomy `happyfiles_category`
- [x] **2.3** `WpMediaFolderDriver.php` — taxonomy `wpmf-category`
- [x] **2.4** `MediaLibraryAssistantDriver.php` — taxonomy `attachment_category`
- [x] **2.5** `FileBirdDriver.php` — tables `fbv` / `fbv_attachment_folder`
- [x] **2.6** `RealMediaLibraryDriver.php` — tables `realmedialibrary` / `realmedialibrary_posts`
- [x] **2.7** `CatFoldersDriver.php` — tables `catfolders` / `catfolders_posts`

### Phase 3: Migration Service & REST API

- [x] **3.1** `DetectorService.php` — iterates drivers, returns available sources with stats, `vmfa_migrate_drivers` filter for extensibility
- [x] **3.2** `MigrationService.php` — `preview()`, `start()` (batched via Action Scheduler), `process_batch()`, `get_job()`, `cancel_job()`, folder creation with topological sort + conflict strategy (skip/merge/overwrite)
- [x] **3.3** `MigrationController.php` — REST routes `vmfa-migrate/v1`: `GET /sources`, `GET /sources/{slug}/preview`, `POST /sources/{slug}/migrate`, `GET /jobs/{id}`, `DELETE /jobs/{id}` — all require `manage_options`

### Phase 4: Admin UI (React)

- [x] **4.1** `SettingsTab.php` — extends `AbstractSettingsTab`, tab slug `migrate`, `supports_parent_tabs()` fallback
- [x] **4.2** `src/js/index.js` — React entry point, mounts `MigrationDashboard`
- [x] **4.3** `MigrationDashboard.jsx` — main component: source fetch, preview, migration start, conflict strategy selector
- [x] **4.4** `SourceList.jsx` — detected sources table with select buttons
- [x] **4.5** `PreviewPanel.jsx` — folder tree visualization + stats
- [x] **4.6** `MigrationProgress.jsx` — job polling, progress bar, result summary
- [x] **4.7** `src/wp7-compat.js` — WP 7+ design-token overrides entry

### Phase 5: WP-CLI

- [x] **5.1** `MigrateCommand.php` — `wp vmfa-migrate list-sources` (table/json/csv/yaml formats)
- [x] **5.2** `wp vmfa-migrate preview <slug>` — folder tree + stats (table/json/tree formats)
- [x] **5.3** `wp vmfa-migrate run <slug>` — execute migration with `--dry-run`, `--batch-size`, `--conflict` options, progress bar polling

### Phase 6: Build & Tooling

- [x] **6.1** `npm install` — node_modules with `@wordpress/scripts` and dependencies
- [x] **6.2** `npm run build` — compile JS/CSS into `build/` directory (index.js, index.css, index.asset.php, wp7-compat.js, wp7-compat.asset.php)
- [x] **6.3** Remove stale temp file `install.log`

### Phase 7: Testing & Verification

- [x] **7.1** `composer lint` (PHPCS) — PHP coding standards check
- [x] **7.2** `npm run lint:js` — JS lint
- [x] **7.3** PHPUnit tests — unit tests for drivers with mock data
- [x] **7.4** Vitest — JS component tests
- [x] **7.5** Manual test: activate alongside a source plugin (e.g. Enhanced Media Library) with sample data → detect → preview → migrate → verify folders in VMF
- [x] **7.6** WP-CLI test: `wp vmfa-migrate list-sources`, `wp vmfa-migrate run <slug> --dry-run`
- [x] **7.7** Edge cases: empty source, duplicate folder names, deep hierarchy (10+ levels), source plugin already deactivated

---

## Decisions

- **Single-folder assignment**: VMF is one-folder-per-item. Source plugins with multi-folder → first folder wins (skipped assignments logged).
- **No source data deletion**: Migration is read-only. Users deactivate the source plugin manually.
- **Action Scheduler**: Batched processing for sites with 10k+ media items (same pattern as vmfa-media-cleanup/vmfa-folder-exporter).
- **Conflict strategy**: `skip` (reuse existing VMF folder with same name+parent, default), `merge` (reuse + log), `overwrite` (rename incoming as "Name (Source Plugin)").
- **Works post-deactivation**: Taxonomy drivers fall back to direct `wp_term_taxonomy` queries; table drivers work regardless.
- **PHP 8.3+, WP 6.8+**: Matches other add-ons.

## Further Considerations

1. **Rollback?** Defer to v1.1. Dry-run preview + read-only migration is sufficient for v1.0.
2. **FileBird Pro user-scoped folders** — ignore for v1.0, support common table structure.
3. **Source deactivated before migration** — taxonomy drivers fall back to direct DB queries; table drivers work regardless.

---

## Key Patterns from addon-development.md

- Bootstrap: `Requires Plugins: virtual-media-folders` header, constants (VERSION/FILE/PATH/URL/BASENAME), PSR-4 autoload, Action Scheduler early load, `plugins_loaded` priority 20.
- Plugin.php: extends `AbstractPlugin`. Implement `get_text_domain()`, `get_plugin_file()`. Override `init_services()`, `init_hooks()`, `init_cli()`.
- SettingsTab: extends `AbstractSettingsTab`. Wire via `vmfo_settings_tabs` filter + `vmfo_settings_enqueue_scripts` action with `supports_parent_tabs()` check and standalone fallback.
- Folder ops: `wp_insert_term($name, 'vmfo_folder', ['parent' => $parent_id])` to create, `wp_set_object_terms($id, $term_id, 'vmfo_folder')` to assign.
- GitHub updater: `class-github-updater.php` + `GitHubUpdater::init(...)`.
- REST: namespace `vmfa-migrate/v1`, `manage_options` capability.
