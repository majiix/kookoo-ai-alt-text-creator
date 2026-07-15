<?php
/**
 * Plugin Name: KooKoo AI Alt Text Creator
 * Plugin URI:  https://wordpress.org/plugins/kookoo-ai-alt-text-creator/
 * Description: Automatically generates alt text and titles for images using OpenRouter AI. Adds a generation button to the Media Library list view.
 * Version:     1.8.2
 * Author:      micromax
 * Text Domain: kookoo-ai-alt-text-creator
 * Domain Path: /languages
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AIALTG_VERSION', '1.8.2' );

// Include required classes.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aialtg-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aialtg-generator.php';

// Include Cron class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aialtg-cron.php';

if ( ! class_exists( 'Aialtg_Image_Descriptor' ) ) {
/**
 * Main Plugin Class
 */
class Aialtg_Image_Descriptor {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Initialize Settings.
		new Aialtg_Settings();

		// Initialize Cron.
		new Aialtg_Cron();

		// Media library columns.
		add_filter( 'manage_media_columns', array( $this, 'add_media_column' ) );
		add_action( 'manage_media_custom_column', array( $this, 'manage_media_column_content' ), 10, 2 );

		// Meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_attachment_meta_box' ) );

		// AJAX Handlers.
		add_action( 'wp_ajax_aialtg_generate_meta', array( $this, 'handle_ajax_request' ) );
		add_action( 'wp_ajax_aialtg_reset_progress', array( $this, 'handle_reset_request' ) );
		// New AJAX for fixing JSON errors
		add_action( 'wp_ajax_aialtg_fix_json_errors', array( $this, 'handle_fix_json_errors' ) );
		// New AJAX for retrying failed images
		add_action( 'wp_ajax_aialtg_retry_failed', array( $this, 'handle_retry_failed_request' ) );
		// New AJAX for fetching models
		add_action( 'wp_ajax_aialtg_get_models', array( $this, 'handle_get_models_request' ) );

		// Assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Plugin action links.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ) );

		// Activation & Deactivation.
		register_activation_hook( __FILE__, array( $this, 'activation_logic' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation_cleanup' ) );
	}

	/**
	 * Logic runs on plugin activation.
	 */
	public function activation_logic() {
		// Run schedule logic.
		$options = get_option( 'aialtg_settings' );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		if ( isset( $options['cron_enabled'] ) && '1' === $options['cron_enabled'] ) {
			if ( ! wp_next_scheduled( Aialtg_Cron::CRON_HOOK ) ) {
				wp_schedule_event( time(), Aialtg_Cron::INTERVAL_NAME, Aialtg_Cron::CRON_HOOK );
			}
		}
	}

	/**
	 * Deactivation cleanup.
	 */
	public function deactivation_cleanup() {
		$hook = Aialtg_Cron::CRON_HOOK;
		wp_clear_scheduled_hook( $hook );
		delete_transient( 'aialtg_stats' );
	}

	/**
	 * Adds settings link to plugin action links.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=kookoo-ai-alt-text-creator' ) ) . '">' . esc_html__( 'Settings', 'kookoo-ai-alt-text-creator' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	// --- Existing UI Logic ---

	/**
	 * Add column to Media Library.
	 *
	 * @param array $columns Existing columns.
	 * @return array New columns.
	 */
	public function add_media_column( $columns ) {
		$columns['aialtg_alt_gen'] = __( 'AI Alt Text', 'kookoo-ai-alt-text-creator' );
		return $columns;
	}

	/**
	 * Render column content.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id Post ID.
	 */
	public function manage_media_column_content( $column_name, $post_id ) {
		if ( 'aialtg_alt_gen' !== $column_name ) {
			return;
		}
		if ( ! wp_attachment_is_image( $post_id ) ) {
			echo esc_html__( 'N/A', 'kookoo-ai-alt-text-creator' );
			return;
		}

		// Check supported formats logic here.
		// Verify against allowed MIME types.
		$allowed_mimes = Aialtg_Settings::get_allowed_mimes();
		$current_mime  = get_post_mime_type( $post_id );

		// If allowed_mimes is strict array and current isn't in it.
		if ( is_array( $allowed_mimes ) && ! in_array( $current_mime, $allowed_mimes, true ) ) {
			echo esc_html__( 'N/A', 'kookoo-ai-alt-text-creator' );
			return;
		}

		$has_alt = get_post_meta( $post_id, '_wp_attachment_image_alt', true );
		$status_class = $has_alt ? 'button-secondary' : 'button-primary';
		$button_text  = $has_alt ? __( 'Regenerate', 'kookoo-ai-alt-text-creator' ) : __( 'Generate Info', 'kookoo-ai-alt-text-creator' );

		?>
		<div class="aialtg-descriptor-wrap">
			<button type="button"
					class="button <?php echo esc_attr( $status_class ); ?> aialtg-generate-btn"
					data-id="<?php echo esc_attr( $post_id ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'aialtg_gen_nonce_' . $post_id ) ); ?>">
				<?php echo esc_html( $button_text ); ?>
			</button>
			<span class="spinner aialtg-spinner"></span>
			<div class="aialtg-message"></div>
		</div>
		<?php
	}

	/**
	 * Add meta box to Edit Media screen.
	 */
	public function add_attachment_meta_box() {
		add_meta_box(
			'aialtg_meta_box',
			__( 'AI Generation Info', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_meta_box' ),
			'attachment',
			'side',
			'low'
		);
	}

	/**
	 * Render meta box content.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_meta_box( $post ) {
		$date = get_post_meta( $post->ID, '_aialtg_gen_date', true );
		$source = get_post_meta( $post->ID, '_aialtg_gen_source', true );
		$error = get_post_meta( $post->ID, '_aialtg_error_log', true );

		if ( ! empty( $error ) ) {
			echo '<div style="color: #d63638; margin-bottom: 10px;">';
			echo '<strong>' . esc_html__( 'Generation Failed:', 'kookoo-ai-alt-text-creator' ) . '</strong><br>';
			echo esc_html( $error );
			echo '</div>';
		}

		if ( empty( $date ) && empty( $error ) ) {
			echo '<p>' . esc_html__( 'No generation info available.', 'kookoo-ai-alt-text-creator' ) . '</p>';
			return;
		}

		if ( ! empty( $date ) ) {
			echo '<p><strong>' . esc_html__( 'Last Generated:', 'kookoo-ai-alt-text-creator' ) . '</strong><br>' . esc_html( $date ) . '</p>';
			echo '<p><strong>' . esc_html__( 'Source:', 'kookoo-ai-alt-text-creator' ) . '</strong> ' . esc_html( ucfirst( $source ) ) . '</p>';
		}
	}

	/**
	 * AJAX Handler for Generation.
	 */
	public function handle_ajax_request() {
		try {
			$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
			if ( ! $post_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid ID', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! isset( $_POST['nonce'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Nonce missing', 'kookoo-ai-alt-text-creator' ) ) );
			}

			// Fix: Unslash before sanitizing.
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aialtg_gen_nonce_' . $post_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'kookoo-ai-alt-text-creator' ) ) );
			}

			// Use the Generator Class.
			$generator = new Aialtg_Generator();
			$result = $generator->process_image( $post_id, 'manual' );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			} else {
				// Clear stats cache.
				delete_transient( 'aialtg_stats' );

				wp_send_json_success( array(
					'message'  => __( 'Info Saved!', 'kookoo-ai-alt-text-creator' ),
					'alt_text' => $result['alt_text'],
					'title'    => $result['title'],
				) );
			}
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX Handler for Resetting Progress.
	 */
	public function handle_reset_request() {
		try {
			if ( ! isset( $_POST['nonce'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Nonce missing', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aialtg_reset_nonce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'kookoo-ai-alt-text-creator' ) ) );
			}

			global $wpdb;
			// Delete the meta key from all posts to reset "processed" status.
			// Use direct query for performance on large datasets to avoid loading IDs into memory.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_aialtg_processed' ) );

			// Clean up error logs as well when resetting.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_aialtg_error_log' ) );

			// Clear cached stats.
			delete_transient( 'aialtg_stats' );

			wp_send_json_success( array( 'message' => __( 'Progress reset successfully.', 'kookoo-ai-alt-text-creator' ) ) );
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX Handler for Retrying Failed Images.
	 */
	public function handle_retry_failed_request() {
		try {
			if ( ! isset( $_POST['nonce'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Nonce missing', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aialtg_retry_failed_nonce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'kookoo-ai-alt-text-creator' ) ) );
			}

			global $wpdb;
			// Delete _aialtg_processed meta ONLY where value is 'failed'.
			// This puts them back into the "Not Processed" (Pending) queue for the Cron job.

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->query( $wpdb->prepare(
				"DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
				'_aialtg_processed',
				'failed'
			) );

			// Clear cached stats.
			delete_transient( 'aialtg_stats' );

			if ( $count ) {
				/* translators: %d: Number of images moved to queue */
				$message = sprintf( __( 'Moved %d failed images back to the processing queue.', 'kookoo-ai-alt-text-creator' ), $count );
			} else {
				$message = __( 'No failed images found.', 'kookoo-ai-alt-text-creator' );
			}

			wp_send_json_success( array( 'message' => $message ) );
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX Handler for Scanning and Fixing JSON Errors.
	 *
	 * Detects images where raw JSON (e.g. {"alt_text": ...}) was accidentally saved as the Alt Text or Title.
	 * Resets their processed status so they are regenerated.
	 */
	public function handle_fix_json_errors() {
		try {
			if ( ! isset( $_POST['nonce'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Nonce missing', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aialtg_fix_json_nonce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'kookoo-ai-alt-text-creator' ) ) );
			}

			global $wpdb;

			// Perform a targeted query to find processed images that look like they contain raw JSON.
			// We look for common JSON keys/markers in Alt Text meta or Post Title.
			// Patterns: {"alt_text", {"title", ```json
			// We join with our processed flag to only target handled images.
			// Updated to find "alt_text" or "title" key strings even if there are spaces/newlines.

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results( "
				SELECT p.ID
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm_alt ON (p.ID = pm_alt.post_id AND pm_alt.meta_key = '_wp_attachment_image_alt')
				INNER JOIN {$wpdb->postmeta} pm_proc ON (p.ID = pm_proc.post_id AND pm_proc.meta_key = '_aialtg_processed')
				WHERE
				(pm_alt.meta_value LIKE '%\"alt_text\"%' OR pm_alt.meta_value LIKE '%```json%')
				OR
				(p.post_title LIKE '%\"title\"%' OR p.post_title LIKE '%```json%')
			" );

			$count = 0;
			if ( is_array( $results ) && ! empty( $results ) ) {
				$ids = wp_list_pluck( $results, 'ID' );
				$ids = array_map( 'intval', $ids );
				$count = count( $ids );

				// Delete postmeta for all these IDs at once using SQL query.
				$ids_placeholder = implode( ',', array_fill( 0, $count, '%d' ) );

				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$wpdb->query( $wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($ids_placeholder) AND meta_key IN ('_aialtg_processed', '_aialtg_error_log')",
					$ids
				) );
				// phpcs:enable

				// Clean object cache for these posts.
				foreach ( $ids as $id ) {
					clean_post_cache( $id );
				}

				// Clear stats cache so the UI updates pending count.
				delete_transient( 'aialtg_stats' );
			}

			if ( $count > 0 ) {
				/* translators: %d: number of images fixed */
				$message = sprintf( __( 'Found and reset %d images with bad JSON data. They are now pending regeneration.', 'kookoo-ai-alt-text-creator' ), $count );
			} else {
				$message = __( 'No JSON errors found.', 'kookoo-ai-alt-text-creator' );
			}

			wp_send_json_success( array( 'message' => $message ) );
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX Handler for fetching OpenRouter models.
	 */
	public function handle_get_models_request() {
		try {
			if ( ! isset( $_POST['nonce'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Nonce missing', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aialtg_models_nonce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'kookoo-ai-alt-text-creator' ) ) );
			}

			// Check transient cache first.
			$models = get_transient( 'aialtg_openrouter_models' );

			if ( false === $models ) {
				$response = wp_remote_get( 'https://openrouter.ai/api/v1/models', array( 'timeout' => 15 ) );

				if ( is_wp_error( $response ) ) {
					wp_send_json_error( array( 'message' => $response->get_error_message() ) );
				}

				$response_code = wp_remote_retrieve_response_code( $response );
				if ( 200 !== $response_code ) {
					/* translators: %d: HTTP status code */
					wp_send_json_error( array( 'message' => sprintf( __( 'API returned HTTP %d', 'kookoo-ai-alt-text-creator' ), $response_code ) ) );
				}

				$body = wp_remote_retrieve_body( $response );
				$data = json_decode( $body, true );

				if ( ! is_array( $data ) || ! isset( $data['data'] ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid data format from API', 'kookoo-ai-alt-text-creator' ) ) );
				}

				$models = $data['data'];

				// Cache models list for 24 hours.
				set_transient( 'aialtg_openrouter_models', $models, DAY_IN_SECONDS );
			}

			wp_send_json_success( array( 'models' => $models ) );
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Enqueue Assets.
	 *
	 * @param string $hook Admin hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Enqueue on Upload (Media Library) AND our Settings page.
		if ( 'upload.php' !== $hook && 'settings_page_kookoo-ai-alt-text-creator' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'aialtg-admin-css', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), '1.8.2' );
		wp_enqueue_script( 'aialtg-admin-js', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), '1.8.2', true );

		wp_localize_script( 'aialtg-admin-js', 'aialtg_vars', array(
			'processing'    => __( 'Processing...', 'kookoo-ai-alt-text-creator' ),
			'regenerate'    => __( 'Regenerate', 'kookoo-ai-alt-text-creator' ),
			'network_error' => __( 'Network error', 'kookoo-ai-alt-text-creator' ),
			'reset_confirm' => __( 'Are you sure you want to reset the progress? The cron job will re-analyze all images. This does not delete existing Alt Texts.', 'kookoo-ai-alt-text-creator' ),
		) );
	}
}
}

new Aialtg_Image_Descriptor();