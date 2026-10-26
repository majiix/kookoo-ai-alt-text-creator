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
 * Clean up plugin ephemeral data.
 *
 * Process:
 * 1. Clear scheduled cron jobs.
 * 2. Delete plugin transients (cache).
 *
 * Note: Plugin settings ('aialtg_settings') and image generation postmeta
 * ('_aialtg_processed', '_aialtg_error_log', '_aialtg_gen_date', '_aialtg_gen_source')
 * are intentionally preserved to ensure seamless data continuity if the user
 * reinstalls the plugin. Native image metadata ('_wp_attachment_image_alt', post_title)
 * also remains permanently stored.
 */

// 1. Clear the Scheduled Hook.
wp_clear_scheduled_hook( 'aialtg_cron_process_images' );

// 2. Delete Transients.
delete_transient( 'aialtg_stats' );
delete_transient( 'aialtg_openrouter_models' );
