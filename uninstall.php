<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package AI_Alt_Text_Generator
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data.
 *
 * Process:
 * 1. Clear scheduled cron jobs.
 * 2. Delete plugin settings (options).
 * 3. Delete plugin transients (cache).
 * 4. Delete custom post meta data attached to images.
 */

// 1. Clear the Scheduled Hook.
wp_clear_scheduled_hook( 'aialtg_cron_process_images' );

// 2. Delete Plugin Settings.
delete_option( 'aialtg_settings' );

// 3. Delete Transients.
delete_transient( 'aialtg_stats' );
delete_transient( 'aialtg_license_check_lock' );
delete_transient( 'aialtg_openrouter_models' );

// 4. Delete Post Meta Data.
// We use direct SQL for performance to avoid loading every post ID into memory via get_posts().
global $wpdb;

// The meta keys used by this plugin.
$aialtg_meta_keys = array(
	'_aialtg_processed',   // Flag indicating image was processed.
	'_aialtg_gen_date',    // Timestamp of generation.
	'_aialtg_gen_source',  // Source (manual vs cron).
	'_aialtg_error_log',   // Error logs for failed generations.
);

// Delete metadata in a single query.
// Note: We deliberately do NOT delete '_wp_attachment_image_alt' as that is native WP data
// and the user likely wants to keep the generated alt text even if they delete the plugin.
$aialtg_placeholders = implode( ',', array_fill( 0, count( $aialtg_meta_keys ), '%s' ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ($aialtg_placeholders)", $aialtg_meta_keys ) );