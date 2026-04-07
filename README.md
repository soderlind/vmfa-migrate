# Virtual Media Folders - Migrate

Migration add-on for [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders). Import folders and file assignments from other media folder plugins.

## Supported Plugins

| Plugin | Storage | Status |
|--------|---------|--------|
| [Enhanced Media Library](https://wordpress.org/plugins/enhanced-media-library/) | Taxonomy `media_category` | ✅ Supported |
| [FileBird](https://wordpress.org/plugins/filebird/) | Custom tables `fbv` | ✅ Supported |
| [Real Media Library](https://wordpress.org/plugins/real-media-library-lite/) | Custom tables `realmedialibrary` | ✅ Supported |
| [HappyFiles](https://wordpress.org/plugins/happyfiles/) | Taxonomy `happyfiles_category` | ✅ Supported |
| [WP Media Folder](https://www.joomunited.com/wordpress-products/wp-media-folder) | Taxonomy `wpmf-category` | ✅ Supported |
| [Media Library Assistant](https://wordpress.org/plugins/media-library-assistant/) | Taxonomy `attachment_category` | ✅ Supported |
| [CatFolders](https://wordpress.org/plugins/catfolders/) | Custom tables `catfolders` | ✅ Supported |

## Requirements

- WordPress 6.8+
- PHP 8.3+
- [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders) 2.0.0+

## Installation

1. Download [`vmfa-migrate.zip`](https://github.com/soderlind/vmfa-migrate/releases/latest/download/vmfa-migrate.zip)
2. Upload via `Plugins → Add New → Upload Plugin`
3. Activate via `WordPress Admin → Plugins`

Plugin [updates are handled automatically](https://github.com/soderlind/wordpress-plugin-github-updater#readme) via GitHub. No need to manually download and install updates.


## How It Works

1. **Detection** — The plugin automatically detects which supported media folder plugins have data in your database (works even if the source plugin has been deactivated).
2. **Preview** — See which folders will be created and how many assignments will be migrated before committing.
3. **Migration** — Folders are created as VMF taxonomy terms (`vmfo_folder`). Attachment assignments are batched via Action Scheduler for large libraries.
4. **Non-destructive** — Source plugin data is never modified or deleted. Existing VMF folder assignments are preserved.

## Conflict Strategies

When a VMF folder with the same name and parent already exists:

- **Skip** (default) — Reuse the existing folder.
- **Merge** — Same as skip, logged for review.
- **Overwrite** — Create a new folder with a deduplicated name.

## Usage

### Admin UI

Navigate to **Media → Folder Settings → Migration** to detect sources, preview, and start migrations.

### WP-CLI

```bash
# List detected migration sources
wp vmfa-migrate list-sources

# Preview what will be migrated
wp vmfa-migrate preview enhanced-media-library

# Preview as a tree
wp vmfa-migrate preview enhanced-media-library --format=tree

# Run the migration
wp vmfa-migrate run enhanced-media-library

# Dry run
wp vmfa-migrate run enhanced-media-library --dry-run

# With options
wp vmfa-migrate run filebird --batch-size=200 --conflict=merge

# Include additional taxonomies (EML / MLA only)
wp vmfa-migrate run enhanced-media-library --include-taxonomies
```

### Taxonomy Migration

Enhanced Media Library and Media Library Assistant support additional taxonomies (e.g. media tags) beyond their primary folder taxonomy. When you enable **Include taxonomies** (UI checkbox or `--include-taxonomies` in WP-CLI), these are migrated as standard WordPress taxonomies on each attachment.

Virtual Media Folders does not display these taxonomies in its sidebar. They are stored so the data is not lost. You can access them with native WordPress functions.

**Register the taxonomy** (e.g. in your theme's `functions.php` or a custom plugin):

```php
add_action( 'init', function () {
    register_taxonomy( 'media_tag', 'attachment', [
        'label'        => __( 'Media Tags' ),
        'public'       => true,
        'hierarchical' => false,
        'show_ui'      => true,
        'show_in_rest' => true,
    ] );
} );
```

**Get terms for an attachment:**

```php
$terms = get_the_terms( $attachment_id, 'media_tag' );
if ( $terms && ! is_wp_error( $terms ) ) {
    foreach ( $terms as $term ) {
        echo esc_html( $term->name );
    }
}
```

**Query attachments by term:**

```php
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
```

Replace `media_tag` with the actual taxonomy slug shown during migration (e.g. `attachment_tag` for Media Library Assistant).

### REST API

All endpoints require `manage_options` capability.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/wp-json/vmfa-migrate/v1/sources` | List detected sources |
| GET | `/wp-json/vmfa-migrate/v1/sources/{slug}/preview` | Preview migration |
| POST | `/wp-json/vmfa-migrate/v1/sources/{slug}/migrate` | Start migration |
| GET | `/wp-json/vmfa-migrate/v1/jobs/{id}` | Get job progress |
| DELETE | `/wp-json/vmfa-migrate/v1/jobs/{id}` | Cancel a job |

## Extending

Add custom drivers by filtering `vmfa_migrate_drivers`:

```php
add_filter( 'vmfa_migrate_drivers', function( array $drivers ): array {
    $drivers[] = MyCustomDriver::class;
    return $drivers;
} );
```

Your driver must implement `VmfaMigrate\Drivers\DriverInterface`.

## Development

```bash
composer install
npm install
npm run build
composer test
npm test
```

## License

GPL-2.0-or-later
