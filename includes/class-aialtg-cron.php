<?php
/**
 * Cron Class for AI Alt Text Creator
 * Handles background processing and scheduling.
 *
 * @package AI_Alt_Text_Generator
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Aialtg_Cron' ) ) {
/**
 * Handles Cron Logic.
 */
class Aialtg_Cron {

	/**
	 * Option key.
	 *
	 * @var string
	 */
	private $option_name = 'aialtg_settings';

	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	public const CRON_HOOK = 'aialtg_cron_process_images';

	/**
	 * Custom interval name.
	 *
	 * @var string
	 */
	public const INTERVAL_NAME = 'aialtg_custom_interval';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register Schedule.
		add_filter( 'cron_schedules', array( $this, 'add_custom_cron_schedule' ) );

		// Register Worker.
		add_action( self::CRON_HOOK, array( $this, 'process_cron_batch' ) );
	}

	/**
	 * Adds a custom interval to WP Cron based on settings.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules.
	 */
	public function add_custom_cron_schedule( $schedules ) {
		$options = get_option( $this->option_name );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$minutes = isset( $options['cron_interval'] ) ? (int) $options['cron_interval'] : 5;
		if ( $minutes < 1 ) {
			$minutes = 1;
		}

		$schedules[ self::INTERVAL_NAME ] = array(
			'interval' => $minutes * 60,
			/* translators: %d: number of minutes */
			'display'  => sprintf( __( 'Every %d Minutes (AI Alt Text)', 'kookoo-ai-alt-text-creator' ), $minutes ),
		);
		return $schedules;
	}

	/**
	 * Cron Worker: Processes a batch of images.
	 */
	public function process_cron_batch() {
		$options = get_option( $this->option_name );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		// Double check if enabled.
		if ( empty( $options['cron_enabled'] ) ) {
			return;
		}

		$batch_size = isset( $options['cron_batch_size'] ) ? (int) $options['cron_batch_size'] : 1;
		if ( $batch_size < 1 ) {
			$batch_size = 1;
		}

		// Use the allowed types for the query.
		$allowed_mimes = Aialtg_Settings::get_allowed_mimes();

		// Query images that have NOT been processed by the plugin.
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => $allowed_mimes,
			'posts_per_page' => $batch_size,
			'fields'         => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(
				array(
					'key'     => '_aialtg_processed',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$images = get_posts( $args );

		if ( ! empty( $images ) ) {
			$generator = new Aialtg_Generator();
			foreach ( $images as $image_id ) {
				// Extend execution time limit for this request if function is available.
				if ( function_exists( 'set_time_limit' ) ) {
					// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
					@set_time_limit( 60 );
				}
				$result = $generator->process_image( $image_id, 'cron' );

				// Handle failures.
				if ( is_wp_error( $result ) ) {
					// Mark as processed (but failed) so we move on and don't block the queue.
					// This effectively removes it from 'Pending'.
					update_post_meta( $image_id, '_aialtg_processed', 'failed' );

					// Save the error so the user knows what happened.
					// Use substr to keep it short if the API returns a massive error dump.
					update_post_meta( $image_id, '_aialtg_error_log', substr( $result->get_error_message(), 0, 250 ) );
				} else {
					// Cleanup any previous error log if it succeeded this time.
					delete_post_meta( $image_id, '_aialtg_error_log' );
				}

				// Optional: Add a small delay between requests if batch > 1.
				if ( count( $images ) > 1 ) {
					sleep( 1 );
				}
			}
			// Clear stats cache so it updates on next page load.
			delete_transient( 'aialtg_stats' );
		}
	}
}
}