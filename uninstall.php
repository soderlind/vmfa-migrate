<?php
/**
 * Uninstall handler for VMFA Migrate.
 *
 * Fired when the plugin is deleted via the WordPress admin.
 * Removes all plugin data (job options).
 *
 * @package VmfaMigrate
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete all migration job options.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'vmfa_migrate_job_' ) . '%'
	)
);
