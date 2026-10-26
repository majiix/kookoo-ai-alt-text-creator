<?php
/**
 * Plugin Name: KooKoo AI Alt Text Creator
 * Plugin URI:  https://wordpress.org/plugins/kookoo-ai-alt-text-creator/
 * Description: Automatically generates alt text and titles for images using AI via OpenRouter.
 * Version:     2.5.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
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

if ( ! defined( 'AIALTG_VERSION' ) ) {
	define( 'AIALTG_VERSION', '2.5.0' );
}

// Include required classes.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aialtg-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aialtg-generator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aialtg-cron.php';

if ( ! function_exists( 'aialtg_process_image' ) ) {
	/**
	 * Process image metadata generation for an attachment.
	 *
	 * @param int    $post_id Attachment post ID.
	 * @param string $source  Source of generation ('manual', 'cron', 'upload', 'bulk').
	 * @return array|WP_Error Generated metadata result or error.
	 */
	function aialtg_process_image( $post_id, $source = 'manual' ) {
		$generator = new Aialtg_Generator();
		return $generator->process_image( $post_id, $source );
	}
}

// Action hook for background batch processing.
add_action( 'aialtg_process_batch', array( 'Aialtg_Cron', 'process_cron_batch' ) );

/**
 * Activation callback for free plugin.
 */
function aialtg_free_activate() {
	$pro_plugin = 'kookoo-ai-alt-text-creator-pro/kookoo-ai-alt-text-creator-pro.php';
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( is_plugin_active( $pro_plugin ) ) {
		deactivate_plugins( $pro_plugin, true );
		set_transient( 'aialtg_free_activated_notice', 1, 60 );
	}

	$options = get_option( 'aialtg_settings' );
	if ( is_array( $options ) && isset( $options['cron_enabled'] ) && '1' === $options['cron_enabled'] ) {
		if ( ! wp_next_scheduled( Aialtg_Cron::CRON_HOOK ) ) {
			wp_schedule_event( time(), Aialtg_Cron::INTERVAL_NAME, Aialtg_Cron::CRON_HOOK );
		}
	}
}
register_activation_hook( __FILE__, 'aialtg_free_activate' );

/**
 * Deactivation callback for free plugin.
 */
function aialtg_free_deactivate() {
	wp_clear_scheduled_hook( Aialtg_Cron::CRON_HOOK );
	delete_transient( 'aialtg_stats' );
}
register_deactivation_hook( __FILE__, 'aialtg_free_deactivate' );

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
		add_action( 'wp_ajax_aialtg_fix_json_errors', array( $this, 'handle_fix_json_errors' ) );
		add_action( 'wp_ajax_aialtg_retry_failed', array( $this, 'handle_retry_failed_request' ) );
		add_action( 'wp_ajax_aialtg_get_models', array( $this, 'handle_get_models_request' ) );
		add_action( 'wp_ajax_aialtg_test_connection', array( $this, 'handle_test_connection_request' ) );

		// Privacy Policy Content.
		add_action( 'admin_init', array( $this, 'register_privacy_policy_content' ) );

		// Assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Plugin action links.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ) );

		// Conflict resolution & deactivation.
		add_action( 'admin_init', array( $this, 'check_plugin_conflicts' ) );
		add_action( 'admin_notices', array( $this, 'deactivation_advisory_notices' ) );

		// Fire action hook to allow addons/extensions to boot cleanly.
		do_action( 'aialtg_loaded' );
	}

	/**
	 * Registers privacy policy content.
	 */
	public function register_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			/* translators: %s: OpenRouter privacy URL */
			__( 'When generating image metadata using KooKoo AI Alt Text Creator, image attachment URLs (or Base64-encoded image data for local installations), post titles, and post contents are transmitted to OpenRouter (%s). This data is processed in accordance with the OpenRouter privacy policy. No personal user data is transmitted or stored externally.', 'kookoo-ai-alt-text-creator' ),
			'https://openrouter.ai/privacy'
		);

		wp_add_privacy_policy_content(
			__( 'KooKoo AI Alt Text Creator', 'kookoo-ai-alt-text-creator' ),
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	/**
	 * Checks for conflicting plugins and deactivates them.
	 */
	public function check_plugin_conflicts() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Deactivate legacy addon if still present.
		$addon_file = 'kookoo-ai-alt-text-creator-pro-addon/kookoo-ai-alt-text-creator-pro-addon.php';
		if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $addon_file ) ) {
			deactivate_plugins( $addon_file, true );
		}

		$pro_plugin = 'kookoo-ai-alt-text-creator-pro/kookoo-ai-alt-text-creator-pro.php';
		if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $pro_plugin ) ) {
			// If user is currently activating Pro, do not deactivate Pro.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['action'], $_GET['plugin'] ) && 'activate' === $_GET['action'] && false !== strpos( sanitize_text_field( wp_unslash( $_GET['plugin'] ) ), 'kookoo-ai-alt-text-creator-pro' ) ) {
				return;
			}
			// If Pro was just activated, do not deactivate Pro.
			if ( get_transient( 'aialtg_pro_activated_notice' ) ) {
				return;
			}

			// If Free was just activated, ensure Pro is deactivated.
			if ( get_transient( 'aialtg_free_activated_notice' ) ) {
				deactivate_plugins( $pro_plugin, true );
			}
		}
	}

	/**
	 * Displays advisory notice if the Pro version was deactivated upon Free activation.
	 */
	public function deactivation_advisory_notices() {
		if ( get_transient( 'aialtg_free_activated_notice' ) ) {
			delete_transient( 'aialtg_free_activated_notice' );
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					echo wp_kses_post( __( '<strong>KooKoo AI Alt Text Creator:</strong> The Pro version (KooKoo AI Alt Text Creator Pro) has been automatically deactivated.', 'kookoo-ai-alt-text-creator' ) );
					?>
				</p>
			</div>
			<?php
		}
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

	/**
	 * Add column to Media Library.
	 *
	 * @param array $columns Existing columns.
	 * @return array New columns.
	 */
	public function add_media_column( $columns ) {
		$columns['aialtg_alt_gen'] = __( 'AI Info', 'kookoo-ai-alt-text-creator' );
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

		$allowed_mimes = Aialtg_Settings::get_allowed_mimes();
		$current_mime  = get_post_mime_type( $post_id );

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
		$error      = get_post_meta( $post->ID, '_aialtg_error_log', true );
		$processed  = get_post_meta( $post->ID, '_aialtg_processed', true );
		$gen_date   = get_post_meta( $post->ID, '_aialtg_gen_date', true );
		$gen_source = get_post_meta( $post->ID, '_aialtg_gen_source', true );

		if ( ! empty( $error ) ) {
			echo '<div style="color: #d63638; margin-bottom: 10px;">';
			echo '<strong>' . esc_html__( 'Generation Failed:', 'kookoo-ai-alt-text-creator' ) . '</strong><br>';
			echo esc_html( $error );
			echo '</div>';
		} elseif ( '1' === $processed ) {
			echo '<p style="color: #059669; font-weight: 600; margin-bottom: 8px;">' . esc_html__( 'Status: Generated with AI', 'kookoo-ai-alt-text-creator' ) . '</p>';
		}

		if ( ! empty( $gen_date ) ) {
			?>
			<p>
				<strong><?php esc_html_e( 'Generated On:', 'kookoo-ai-alt-text-creator' ); ?></strong>
				<span><?php echo esc_html( $gen_date ); ?></span>
			</p>
			<?php
		}

		if ( ! empty( $gen_source ) ) {
			?>
			<p>
				<strong><?php esc_html_e( 'Generation Source:', 'kookoo-ai-alt-text-creator' ); ?></strong>
				<span><?php echo esc_html( ucfirst( $gen_source ) ); ?></span>
			</p>
			<?php
		}

		ob_start();
		do_action( 'aialtg_attachment_meta_box_details', $post );
		$extra_details = ob_get_clean();

		if ( ! empty( $extra_details ) ) {
			echo $extra_details; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( empty( $error ) && '1' !== $processed && empty( $gen_date ) ) {
			echo '<p>' . esc_html__( 'No generation info available.', 'kookoo-ai-alt-text-creator' ) . '</p>';
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

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aialtg_gen_nonce_' . $post_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'kookoo-ai-alt-text-creator' ) ) );
			}

			$result = aialtg_process_image( $post_id, 'manual' );

			if ( is_wp_error( $result ) ) {
				update_post_meta( $post_id, '_aialtg_processed', 'failed' );
				update_post_meta( $post_id, '_aialtg_error_log', substr( $result->get_error_message(), 0, 250 ) );
				delete_transient( 'aialtg_stats' );
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			} else {
				delete_transient( 'aialtg_stats' );

				wp_send_json_success( array(
					'message'  => __( 'Info Saved!', 'kookoo-ai-alt-text-creator' ),
					'alt_text' => $result['alt_text'] ?? '',
					'title'    => $result['title'] ?? '',
				) );
			}
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => __( 'An unexpected system error occurred. Please check server logs.', 'kookoo-ai-alt-text-creator' ) ) );
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
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_aialtg_processed' ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_aialtg_error_log' ) );

			delete_transient( 'aialtg_stats' );

			wp_send_json_success( array( 'message' => __( 'Progress reset successfully.', 'kookoo-ai-alt-text-creator' ) ) );
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => __( 'An unexpected system error occurred. Please check server logs.', 'kookoo-ai-alt-text-creator' ) ) );
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
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->query( $wpdb->prepare(
				"DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
				'_aialtg_processed',
				'failed'
			) );

			delete_transient( 'aialtg_stats' );

			if ( $count ) {
				/* translators: %d: Number of images moved to queue */
				$message = sprintf( __( 'Moved %d failed images back to the processing queue.', 'kookoo-ai-alt-text-creator' ), $count );
			} else {
				$message = __( 'No failed images found.', 'kookoo-ai-alt-text-creator' );
			}

			wp_send_json_success( array( 'message' => $message ) );
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => __( 'An unexpected system error occurred. Please check server logs.', 'kookoo-ai-alt-text-creator' ) ) );
		}
	}

	/**
	 * AJAX Handler for Scanning and Fixing JSON Errors.
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

				$ids_placeholder = implode( ',', array_fill( 0, count, '%d' ) );

				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$wpdb->query( $wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($ids_placeholder) AND meta_key IN ('_aialtg_processed', '_aialtg_error_log')",
					$ids
				) );
				// phpcs:enable

				foreach ( $ids as $id ) {
					clean_post_cache( $id );
				}

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
			wp_send_json_error( array( 'message' => __( 'An unexpected system error occurred. Please check server logs.', 'kookoo-ai-alt-text-creator' ) ) );
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
				set_transient( 'aialtg_openrouter_models', $models, DAY_IN_SECONDS );
			}

			wp_send_json_success( array( 'models' => $models ) );
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => __( 'An unexpected system error occurred. Please check server logs.', 'kookoo-ai-alt-text-creator' ) ) );
		}
	}

	/**
	 * AJAX Handler for testing API connection across providers.
	 */
	public function handle_test_connection_request() {
		try {
			if ( ! isset( $_POST['nonce'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Nonce missing', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aialtg_test_conn_nonce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'kookoo-ai-alt-text-creator' ) ) );
			}

			$gateway = isset( $_POST['api_gateway'] ) ? sanitize_text_field( wp_unslash( $_POST['api_gateway'] ) ) : 'openrouter';
			$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
			$model   = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';

			$allowed_gateways = apply_filters( 'aialtg_allowed_gateways', array( 'openrouter' ) );
			if ( ! in_array( $gateway, $allowed_gateways, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Selected API Gateway is not supported.', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( empty( $api_key ) ) {
				wp_send_json_error( array( 'message' => __( 'API Key is empty. Please enter your key first.', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( empty( $model ) ) {
				wp_send_json_error( array( 'message' => __( 'Please select or enter an AI Model first.', 'kookoo-ai-alt-text-creator' ) ) );
			}

			$options = array(
				'api_gateway' => $gateway,
				'api_key'     => $api_key,
			);
			$options = apply_filters( 'aialtg_test_connection_options', $options, $gateway, $api_key );

			$default_url = 'https://openrouter.ai/api/v1/chat/completions';
			$api_url     = apply_filters( 'aialtg_api_url', $default_url, $options );

			$default_headers = array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
				'HTTP-Referer'  => add_query_arg( 'page', 'kookoo-ai-alt-text-creator', get_site_url() ),
				'X-Title'       => wp_strip_all_tags( get_bloginfo( 'name' ) ),
			);
			$headers = apply_filters( 'aialtg_api_headers', $default_headers, $options );
			$model   = apply_filters( 'aialtg_api_model', $model, $gateway, $options );

			$body = array(
				'model'      => $model,
				'max_tokens' => 10,
				'messages'   => array(
					array(
						'role'    => 'user',
						'content' => array(
							array(
								'type' => 'text',
								'text' => 'connection test. reply OK.',
							),
							array(
								'type'      => 'image_url',
								'image_url' => array( 'url' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=' ),
							),
						),
					),
				),
			);

			$response = wp_remote_post(
				$api_url,
				array(
					'headers' => $headers,
					'body'    => wp_json_encode( $body ),
					'timeout' => 20,
				)
			);

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $response_code ) {
				$resp_body = wp_remote_retrieve_body( $response );
				$resp_data = json_decode( $resp_body, true );
				$err_msg = '';
				if ( is_array( $resp_data ) && isset( $resp_data['error']['message'] ) ) {
					$err_msg = $resp_data['error']['message'];
				} else {
					$err_msg = wp_remote_retrieve_response_message( $response );
				}
				/* translators: 1: HTTP status code, 2: error message */
				wp_send_json_error( array( 'message' => sprintf( __( 'API returned HTTP %1$d: %2$s', 'kookoo-ai-alt-text-creator' ), $response_code, $err_msg ) ) );
			}

			$resp_body = wp_remote_retrieve_body( $response );
			$resp_data = json_decode( $resp_body, true );

			if ( ! is_array( $resp_data ) || isset( $resp_data['error'] ) ) {
				wp_send_json_error( array( 'message' => isset( $resp_data['error']['message'] ) ? $resp_data['error']['message'] : __( 'Invalid API Key or Model details.', 'kookoo-ai-alt-text-creator' ) ) );
			}

			wp_send_json_success( array( 'message' => __( 'Connection successful! The API Key and Model details are valid.', 'kookoo-ai-alt-text-creator' ) ) );
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => __( 'An unexpected system error occurred. Please check server logs.', 'kookoo-ai-alt-text-creator' ) ) );
		}
	}

	/**
	 * Enqueue Assets.
	 *
	 * @param string $hook Admin hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'upload.php' !== $hook && 'settings_page_kookoo-ai-alt-text-creator' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'aialtg-admin-css', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), AIALTG_VERSION );
		wp_enqueue_script( 'aialtg-admin-js', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), AIALTG_VERSION, true );

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
