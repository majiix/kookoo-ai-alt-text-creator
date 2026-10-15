<?php
/**
 * Settings Class for AI Alt Text Creator
 *
 * @package AI_Alt_Text_Generator
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Aialtg_Settings' ) ) {
/**
 * Handles plugin settings and admin menu.
 */
class Aialtg_Settings {

	/**
	 * Option key for API settings.
	 *
	 * @var string
	 */
	public static $option_name = 'aialtg_settings';

	/**
	 * Cached settings options.
	 *
	 * @var array|null
	 */
	private $options = null;

	/**
	 * Get settings options, cached in instance.
	 *
	 * @return array
	 */
	private function get_options() {
		if ( null === $this->options ) {
			$options = get_option( self::$option_name );
			$this->options = is_array( $options ) ? $options : array();
		}
		return $this->options;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Adds the settings page to the Settings menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'AI Alt Text', 'kookoo-ai-alt-text-creator' ),
			__( 'AI Alt Text', 'kookoo-ai-alt-text-creator' ),
			'manage_options',
			'kookoo-ai-alt-text-creator',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Registers plugin settings.
	 */
	public function register_settings() {
		register_setting( 'aialtg_plugin_options', self::$option_name, array( $this, 'sanitize_settings' ) );

		// --- API Section ---
		add_settings_section(
			'aialtg_api_section',
			__( 'General', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_section_info' ),
			'kookoo-ai-alt-text-creator'
		);

		add_settings_field(
			'api_gateway',
			__( 'API Gateway', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_api_gateway_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_api_section'
		);

		add_settings_field(
			'api_key',
			__( 'OpenRouter API Key', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_api_key_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_api_section'
		);

		add_settings_field(
			'model',
			__( 'AI Model', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_model_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_api_section'
		);

		add_settings_field(
			'test_connection',
			__( 'Test Connection', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_test_connection_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_api_section'
		);

		// --- Options Section ---
		add_settings_section(
			'aialtg_options_section',
			__( 'Generation Options', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_options_section_info' ),
			'kookoo-ai-alt-text-creator'
		);

		// New Global Context Field.
		add_settings_field(
			'global_context',
			__( 'Global Context', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_global_context_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_options_section'
		);

		add_settings_field(
			'allowed_formats',
			__( 'Supported Image Formats', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_allowed_formats_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_options_section'
		);

		add_settings_field(
			'enable_alt',
			__( 'Generate Alt Text', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_enable_alt_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_options_section'
		);

		add_settings_field(
			'prompt',
			__( 'Alt Text Prompt', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_alt_prompt_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_options_section'
		);

		add_settings_field(
			'enable_title',
			__( 'Generate Title', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_enable_title_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_options_section'
		);

		add_settings_field(
			'title_prompt',
			__( 'Title Prompt', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_title_prompt_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_options_section'
		);

		// --- Cron / Bulk Section ---
		add_settings_section(
			'aialtg_cron_section',
			__( 'Bulk Generation (Cron)', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_cron_section_info' ),
			'kookoo-ai-alt-text-creator'
		);

		add_settings_field(
			'cron_enabled',
			__( 'Enable Background Processing', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_cron_enabled_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_cron_section'
		);

		add_settings_field(
			'cron_batch_size',
			__( 'Batch Size (Images per run)', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_cron_batch_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_cron_section'
		);

		add_settings_field(
			'cron_interval',
			__( 'Interval (Minutes)', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_cron_interval_field' ),
			'kookoo-ai-alt-text-creator',
			'aialtg_cron_section'
		);

		// Allow extensions (e.g. Pro Addon) to register additional settings fields or sections.
		do_action( 'aialtg_register_settings_fields', $this );
	}

	/**
	 * Sanitizes setting inputs.
	 */
	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		$new_input = array();

		// API Settings.
		if ( isset( $input['api_gateway'] ) ) {
			$new_input['api_gateway'] = sanitize_text_field( $input['api_gateway'] );
		}
		if ( isset( $input['api_key'] ) ) {
			$new_input['api_key'] = sanitize_text_field( $input['api_key'] );
		}
		if ( isset( $input['model'] ) ) {
			$new_input['model'] = sanitize_text_field( $input['model'] );
		}

		// Feature Toggles.
		$new_input['enable_alt']    = isset( $input['enable_alt'] ) ? '1' : '0';
		$new_input['enable_title']  = isset( $input['enable_title'] ) ? '1' : '0';

		// Prompts & Formats.
		if ( isset( $input['global_context'] ) ) {
			$new_input['global_context'] = wp_strip_all_tags( $input['global_context'] );
		}
		if ( isset( $input['allowed_formats'] ) ) {
			$new_input['allowed_formats'] = sanitize_text_field( $input['allowed_formats'] );
		}
		if ( isset( $input['prompt'] ) ) {
			$new_input['prompt'] = wp_strip_all_tags( $input['prompt'] );
		}
		if ( isset( $input['title_prompt'] ) ) {
			$new_input['title_prompt'] = wp_strip_all_tags( $input['title_prompt'] );
		}

		// Cron Settings.
		$new_input['cron_enabled'] = isset( $input['cron_enabled'] ) ? '1' : '0';

		$batch_size = max( 1, absint( $input['cron_batch_size'] ?? 2 ) );
		$interval   = max( 1, absint( $input['cron_interval'] ?? 5 ) );

		$new_input['cron_batch_size'] = $batch_size;
		$new_input['cron_interval']   = $interval;

		// --- Cron Logic Trigger ---
		$old_options = get_option( self::$option_name );
		$old_options = is_array( $old_options ) ? $old_options : array();

		$old_enabled  = isset( $old_options['cron_enabled'] ) ? $old_options['cron_enabled'] : '0';
		$old_interval = isset( $old_options['cron_interval'] ) ? $old_options['cron_interval'] : 5;

		$needs_reschedule = false;

		if ( $new_input['cron_enabled'] !== $old_enabled ) {
			$needs_reschedule = true;
		}
		if ( '1' === $new_input['cron_enabled'] && $new_input['cron_interval'] !== $old_interval ) {
			$needs_reschedule = true;
		}

		// Check if the event is actually currently scheduled.
		// Access constants via class.
		$cron_hook    = Aialtg_Cron::CRON_HOOK;
		$is_scheduled = wp_next_scheduled( $cron_hook );

		// Logic:
		// 1. If we are disabling it, clear the schedule.
		// 2. If we are enabling it, schedule it IF it's missing OR if we explicitly changed settings (reschedule).
		if ( '0' === $new_input['cron_enabled'] ) {
			if ( $is_scheduled ) {
				wp_clear_scheduled_hook( $cron_hook );
			}
		} elseif ( '1' === $new_input['cron_enabled'] ) {
			// If it's not scheduled in WP (even if enabled in settings), OR we need to reschedule due to changes.
			if ( ! $is_scheduled || $needs_reschedule ) {
				// Clear first to be safe (remove duplicates or old intervals).
				wp_clear_scheduled_hook( $cron_hook );
				wp_schedule_event( time() + 10, Aialtg_Cron::INTERVAL_NAME, $cron_hook );
			}
		}

		// Filter to allow extensions to modify or append sanitized settings.
		$new_input = apply_filters( 'aialtg_sanitize_settings', $new_input, $new_input, $old_options );

		// Clear stats cache whenever settings are saved so counts update immediately.
		delete_transient( 'aialtg_stats' );

		return $new_input;
	}

	/**
	 * Helper: Get allowed MIME types based on settings.
	 *
	 * @return array|string Array of mime types or 'image' string if default.
	 */
	public static function get_allowed_mimes() {
		$options = get_option( self::$option_name );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$input   = isset( $options['allowed_formats'] ) ? $options['allowed_formats'] : 'jpg,jpeg,png,webp';

		// If user cleared the field, fallback to all images? Or strictly nothing?
		// Let's fallback to 'image' (all sub-types) to prevent breakage.
		if ( empty( trim( $input ) ) ) {
			return 'image';
		}

		$exts = array_map( 'trim', explode( ',', strtolower( $input ) ) );

		$mimes    = array();
		$wp_mimes = wp_get_mime_types();

		foreach ( $wp_mimes as $pattern => $type ) {
			// Pattern matches extensions (e.g. 'jpg|jpeg|jpe').
			$type_exts = explode( '|', $pattern );
			// Check if any of the user's allowed extensions match this mime type group.
			if ( array_intersect( $exts, $type_exts ) ) {
				$mimes[] = $type;
			}
		}

		if ( empty( $mimes ) ) {
			return 'image';
		}

		return array_unique( $mimes );
	}

	/**
	 * Get image stats for the dashboard.
	 *
	 * @return array Stats data.
	 */
	private function get_image_stats() {
		$stats = get_transient( 'aialtg_stats' );
		if ( false !== $stats ) {
			return $stats;
		}

		global $wpdb;
		$allowed_mimes = self::get_allowed_mimes();

		if ( is_array( $allowed_mimes ) && ! empty( $allowed_mimes ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $allowed_mimes ), '%s' ) );
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT pm.meta_value as status, COUNT(p.ID) as count
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_aialtg_processed'
				WHERE p.post_type = 'attachment' AND p.post_status = 'inherit' AND p.post_mime_type IN ($placeholders)
				GROUP BY pm.meta_value",
				$allowed_mimes
			) );
			// phpcs:enable
		} else {
			$mime_like = ( is_string( $allowed_mimes ) && ! empty( $allowed_mimes ) ? $allowed_mimes : 'image' ) . '/%';
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT pm.meta_value as status, COUNT(p.ID) as count
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_aialtg_processed'
				WHERE p.post_type = 'attachment' AND p.post_status = 'inherit' AND p.post_mime_type LIKE %s
				GROUP BY pm.meta_value",
				$mime_like
			) );
			// phpcs:enable
		}

		$total     = 0;
		$processed = 0;
		$failed    = 0;

		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {
				$row_count = (int) $row->count;
				$total    += $row_count;
				if ( '1' === $row->status ) {
					$processed = $row_count;
				} elseif ( 'failed' === $row->status ) {
					$failed = $row_count;
				}
			}
		}

		$stats = array(
			'total'     => $total,
			'processed' => $processed,
			'failed'    => $failed,
		);

		set_transient( 'aialtg_stats', $stats, HOUR_IN_SECONDS );
		return $stats;
	}

	/**
	 * Renders Stats Card and Controls.
	 */
	public function render_stats_card() {
		$stats            = $this->get_image_stats();
		$total_images     = $stats['total'];
		$processed_images = $stats['processed'];
		$failed_images    = isset( $stats['failed'] ) ? $stats['failed'] : 0;

		$pending    = max( 0, $total_images - $processed_images - $failed_images );
		$completed  = $processed_images + $failed_images;
		$percentage = $total_images > 0 ? round( ( $completed / $total_images ) * 100 ) : 0;
		?>
		<div class="aialtg-card aialtg-stats-card">
			<div class="aialtg-card-header">
				<h3><?php esc_html_e( 'Statistics', 'kookoo-ai-alt-text-creator' ); ?></h3>
			</div>

			<div class="aialtg-stats-grid">
				<div class="aialtg-stat-item">
					<span class="aialtg-stat-label"><?php esc_html_e( 'Total Images', 'kookoo-ai-alt-text-creator' ); ?></span>
					<span class="aialtg-stat-value"><?php echo absint( $total_images ); ?></span>
				</div>
				<div class="aialtg-stat-item">
					<span class="aialtg-stat-label"><?php esc_html_e( 'Processed', 'kookoo-ai-alt-text-creator' ); ?></span>
					<span class="aialtg-stat-value success"><?php echo absint( $processed_images ); ?></span>
				</div>
				<div class="aialtg-stat-item">
					<span class="aialtg-stat-label"><?php esc_html_e( 'Failed', 'kookoo-ai-alt-text-creator' ); ?></span>
					<span class="aialtg-stat-value error"><?php echo absint( $failed_images ); ?></span>
				</div>
				<div class="aialtg-stat-item">
					<span class="aialtg-stat-label"><?php esc_html_e( 'Pending', 'kookoo-ai-alt-text-creator' ); ?></span>
					<span class="aialtg-stat-value neutral"><?php echo absint( $pending ); ?></span>
				</div>
			</div>

			<div class="aialtg-progress-bar-wrap">
				<div class="aialtg-progress-bar">
					<div class="aialtg-progress-fill" style="width: <?php echo esc_attr( $percentage ); ?>%;"></div>
				</div>
				<span class="aialtg-progress-text"><?php echo esc_html( $percentage ); ?>% <?php esc_html_e( 'Complete', 'kookoo-ai-alt-text-creator' ); ?></span>
			</div>



			<div class="aialtg-controls-wrapper">
				<div class="aialtg-buttons-row">
					<button type="button" class="button button-secondary aialtg-admin-action-btn" data-action="aialtg_reset_progress" data-confirm="1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aialtg_reset_nonce' ) ); ?>">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Reset Progress', 'kookoo-ai-alt-text-creator' ); ?>
					</button>

					<button type="button" class="button button-secondary aialtg-admin-action-btn" data-action="aialtg_retry_failed" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aialtg_retry_failed_nonce' ) ); ?>">
						<span class="dashicons dashicons-redo"></span>
						<?php esc_html_e( 'Retry Failed', 'kookoo-ai-alt-text-creator' ); ?>
					</button>

					<button type="button" class="button button-secondary aialtg-admin-action-btn" data-action="aialtg_fix_json_errors" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aialtg_fix_json_nonce' ) ); ?>">
						<span class="dashicons dashicons-hammer"></span>
						<?php esc_html_e( 'Fix JSON Errors', 'kookoo-ai-alt-text-creator' ); ?>
					</button>
				</div>

				<div class="aialtg-status-area" style="display: none;">
					<span class="spinner aialtg-status-spinner"></span>
					<span class="aialtg-status-message"></span>
				</div>
			</div>

			<div class="aialtg-card-footer">
				<p class="description">
					<?php esc_html_e( 'Resetting will re-analyze all images. Retry Failed moves error images back to pending. Fix JSON repairs accidental raw code in metadata.', 'kookoo-ai-alt-text-creator' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders upgrade card to purchase Pro version.
	 */
	private function render_pro_card() {
		if ( ! apply_filters( 'aialtg_show_pro_card', true ) ) {
			return;
		}
		?>
		<div class="aialtg-card aialtg-upgrade-card">
			<div class="aialtg-card-header">
				<h3>
					<span class="dashicons dashicons-star-filled"></span>
					Upgrade to Pro
				</h3>
			</div>
			<div class="aialtg-card-body">
				<p class="aialtg-upgrade-intro">
					Supercharge your media workflow with advanced automation and direct AI access:
				</p>
				<ul class="aialtg-upgrade-features">
					<li>
						<span class="dashicons dashicons-yes-alt"></span>
						<span><strong>Direct API Gateways:</strong> OpenAI & Google Gemini direct keys</span>
					</li>
					<li>
						<span class="dashicons dashicons-yes-alt"></span>
						<span><strong>Full Metadata:</strong> Captions & Descriptions alongside Alt & Titles</span>
					</li>
					<li>
						<span class="dashicons dashicons-yes-alt"></span>
						<span><strong>Instant Automation:</strong> Auto-generate on new image uploads</span>
					</li>
					<li>
						<span class="dashicons dashicons-yes-alt"></span>
						<span><strong>Media Library Actions:</strong> Bulk process selected images with 1-click</span>
					</li>
					<li>
						<span class="dashicons dashicons-yes-alt"></span>
						<span><strong>Smart Overwrite Rules:</strong> Skip existing fields to save API credits</span>
					</li>
					<li>
						<span class="dashicons dashicons-yes-alt"></span>
						<span><strong>Developer Tools:</strong> WP-CLI commands & metadata auditing</span>
					</li>
				</ul>
				<div class="aialtg-upgrade-actions">
					<a href="https://violo.ir/?p=14" target="_blank" rel="noopener noreferrer" class="button button-primary aialtg-upgrade-btn">
						Get Pro Addon Now
						<span class="dashicons dashicons-external"></span>
					</a>
					<a href="#aialtg-tab-pro" class="aialtg-switch-tab aialtg-preview-link" data-tab="pro">
						<span class="dashicons dashicons-visibility"></span>
						Preview Pro Features
					</a>
				</div>
			</div>
		</div>
		<?php
	}



	/**
	 * Renders Cron section info.
	 */
	public function render_cron_section_info() {
		echo '<p class="aialtg-section-desc">' . esc_html__( 'Configure the background scheduler to process images automatically.', 'kookoo-ai-alt-text-creator' ) . '</p>';
	}

	public function render_section_info() {
		echo '<p class="aialtg-section-desc">' . esc_html__( 'Enter your OpenRouter API credentials to connect your site to advanced models.', 'kookoo-ai-alt-text-creator' ) . '</p>';
	}

	public function render_options_section_info() {
		echo '<p class="aialtg-section-desc">' . esc_html__( 'Configure how details are generated for your images.', 'kookoo-ai-alt-text-creator' ) . '</p>';
	}

	public function render_api_gateway_field() {
		$options = $this->get_options();
		$gateway = isset( $options['api_gateway'] ) ? $options['api_gateway'] : 'openrouter';
		?>
		<div class="aialtg-input-wrap">
			<select name="<?php echo esc_attr( self::$option_name . '[api_gateway]' ); ?>" id="aialtg-api-gateway" class="regular-text">
				<?php
				$gateways = apply_filters( 'aialtg_api_gateways', array(
					'openrouter' => __( 'OpenRouter (Default)', 'kookoo-ai-alt-text-creator' ),
				) );
				foreach ( $gateways as $value => $label ) {
					echo '<option value="' . esc_attr( $value ) . '" ' . selected( $gateway, $value, false ) . '>' . esc_html( $label ) . '</option>';
				}
				?>
			</select>
		</div>
		<?php
	}

	public function render_api_key_field() {
		$options = $this->get_options();
		$value   = isset( $options['api_key'] ) ? $options['api_key'] : '';
		?>
		<div class="aialtg-input-wrap aialtg-password-wrap">
			<input type="password" name="<?php echo esc_attr( self::$option_name . '[api_key]' ); ?>" value="<?php echo esc_attr( $value ); ?>" id="aialtg-api-key" class="regular-text" placeholder="sk-or-..." />
			<button type="button" class="aialtg-toggle-password" aria-label="<?php esc_attr_e( 'Toggle API Key Visibility', 'kookoo-ai-alt-text-creator' ); ?>">
				<span class="dashicons dashicons-visibility"></span>
			</button>
		</div>
		<?php
	}

	public function render_model_field() {
		$options = $this->get_options();
		$value   = isset( $options['model'] ) ? $options['model'] : 'google/gemini-2.5-flash-lite';
		?>
		<div class="aialtg-model-field-container" data-current-value="<?php echo esc_attr( $value ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aialtg_models_nonce' ) ); ?>">
			<!-- Skeleton Loader -->
			<div class="aialtg-skeleton-loader">
				<div class="aialtg-skeleton-input"></div>
			</div>
			<!-- Select Wrap -->
			<div class="aialtg-model-select-wrap" style="display: none;">
				<select id="aialtg-model-select" class="regular-text">
					<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
				</select>
				<!-- Custom Input Wrap -->
				<div class="aialtg-input-wrap aialtg-custom-model-input-wrap" style="display: none; margin-top: 10px;">
					<input type="text" id="aialtg-custom-model-input" class="regular-text" placeholder="e.g. google/gemini-2.5-flash-lite" />
				</div>
				<!-- Hidden real input that holds the actual submitted value -->
				<input type="hidden" name="<?php echo esc_attr( self::$option_name . '[model]' ); ?>" id="aialtg-model-real-input" value="<?php echo esc_attr( $value ); ?>" />
				<p class="description"><?php esc_html_e( 'Select the language model to use.', 'kookoo-ai-alt-text-creator' ); ?></p>
			</div>
		</div>
		<?php
	}

	public function render_test_connection_field() {
		?>
		<div class="aialtg-buttons-row" style="max-width: 350px; display: inline-flex; align-items: center; gap: 10px;">
			<button type="button" id="aialtg-test-conn-btn" class="button button-secondary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aialtg_test_conn_nonce' ) ); ?>">
				<span class="dashicons dashicons-admin-links" style="margin-top: 4px; vertical-align: middle;"></span>
				<?php esc_html_e( 'Test API Connection', 'kookoo-ai-alt-text-creator' ); ?>
			</button>
			<span class="spinner aialtg-test-spinner" style="float: none; margin: 0; vertical-align: middle;"></span>
		</div>
		<div id="aialtg-test-conn-result" style="margin-top: 10px; font-weight: 500; font-size: 13px; display: none;"></div>
		<?php
	}

	public function render_global_context_field() {
		$options = $this->get_options();
		$default = 'Focus on the visual contents of the image. Refer to the associated page title ({post_title}) and content ({post_content}) to provide relevant context.';
		$val     = isset( $options['global_context'] ) ? $options['global_context'] : $default;
		?>
		<div class="aialtg-input-wrap">
			<textarea name="<?php echo esc_attr( self::$option_name . '[global_context]' ); ?>" id="aialtg-global-context" rows="3" cols="50" class="large-text code"><?php echo esc_textarea( $val ); ?></textarea>
			<div style="margin-top: 5px; text-align: right;">
				<button type="button" class="button button-link aialtg-reset-prompt-btn" style="font-size: 11px; padding: 0; min-height: 20px; line-height: 20px; text-decoration: none;" data-target="aialtg-global-context" data-default="<?php echo esc_attr( $default ); ?>"><?php esc_html_e( 'Reset to Default', 'kookoo-ai-alt-text-creator' ); ?></button>
			</div>
			<p class="description"><?php esc_html_e( 'Global instructions included in all requests. Use {post_title} and {post_content} variables here.', 'kookoo-ai-alt-text-creator' ); ?></p>
		</div>
		<?php
	}

	public function render_allowed_formats_field() {
		$options = $this->get_options();
		$val     = isset( $options['allowed_formats'] ) ? $options['allowed_formats'] : 'jpg,jpeg,png,webp';
		?>
		<div class="aialtg-input-wrap">
			<input type="text" name="<?php echo esc_attr( self::$option_name . '[allowed_formats]' ); ?>" value="<?php echo esc_attr( $val ); ?>" class="large-text" />
			<p class="description"><?php esc_html_e( 'Comma-separated list (e.g., jpg, png, webp).', 'kookoo-ai-alt-text-creator' ); ?></p>
		</div>
		<?php
	}

	public function render_enable_alt_field() {
		$options = $this->get_options();
		?>
		<label class="aialtg-toggle">
			<input type="checkbox" name="<?php echo esc_attr( self::$option_name . '[enable_alt]' ); ?>" value="1" <?php checked( isset( $options['enable_alt'] ) ? $options['enable_alt'] : '0', '1' ); ?> />
			<span class="aialtg-toggle-slider"></span>
			<span class="aialtg-toggle-label"><?php esc_html_e( 'Enable Alt Text', 'kookoo-ai-alt-text-creator' ); ?></span>
		</label>
		<?php
	}

	public function render_alt_prompt_field() {
		$options = $this->get_options();
		$default = 'Generate a concise, descriptive alt text for this image. Do not use phrases like "Image of".';
		$val     = isset( $options['prompt'] ) ? $options['prompt'] : $default;
		?>
		<div class="aialtg-input-wrap">
			<textarea name="<?php echo esc_attr( self::$option_name . '[prompt]' ); ?>" id="aialtg-alt-prompt" rows="3" cols="50" class="large-text code"><?php echo esc_textarea( $val ); ?></textarea>
			<div style="margin-top: 5px; text-align: right;">
				<button type="button" class="button button-link aialtg-reset-prompt-btn" style="font-size: 11px; padding: 0; min-height: 20px; line-height: 20px; text-decoration: none;" data-target="aialtg-alt-prompt" data-default="<?php echo esc_attr( $default ); ?>"><?php esc_html_e( 'Reset to Default', 'kookoo-ai-alt-text-creator' ); ?></button>
			</div>
		</div>
		<?php
	}

	public function render_enable_title_field() {
		$options = $this->get_options();
		?>
		<label class="aialtg-toggle">
			<input type="checkbox" name="<?php echo esc_attr( self::$option_name . '[enable_title]' ); ?>" value="1" <?php checked( isset( $options['enable_title'] ) ? $options['enable_title'] : '0', '1' ); ?> />
			<span class="aialtg-toggle-slider"></span>
			<span class="aialtg-toggle-label"><?php esc_html_e( 'Enable Title', 'kookoo-ai-alt-text-creator' ); ?></span>
		</label>
		<?php
	}

	public function render_title_prompt_field() {
		$options = $this->get_options();
		$default = 'Generate a short, descriptive title for this image.';
		$val     = isset( $options['title_prompt'] ) ? $options['title_prompt'] : $default;
		?>
		<div class="aialtg-input-wrap">
			<textarea name="<?php echo esc_attr( self::$option_name . '[title_prompt]' ); ?>" id="aialtg-title-prompt" rows="2" cols="50" class="large-text code"><?php echo esc_textarea( $val ); ?></textarea>
			<div style="margin-top: 5px; text-align: right;">
				<button type="button" class="button button-link aialtg-reset-prompt-btn" style="font-size: 11px; padding: 0; min-height: 20px; line-height: 20px; text-decoration: none;" data-target="aialtg-title-prompt" data-default="<?php echo esc_attr( $default ); ?>"><?php esc_html_e( 'Reset to Default', 'kookoo-ai-alt-text-creator' ); ?></button>
			</div>
		</div>
		<?php
	}

	public function render_cron_enabled_field() {
		$options = $this->get_options();

		$val = isset( $options['cron_enabled'] ) ? $options['cron_enabled'] : '0';
		?>
		<label class="aialtg-toggle">
			<input type="checkbox" name="<?php echo esc_attr( self::$option_name . '[cron_enabled]' ); ?>" value="1" <?php checked( $val, '1' ); ?> />
			<span class="aialtg-toggle-slider"></span>
			<span class="aialtg-toggle-label"><?php esc_html_e( 'Enable Background Processing', 'kookoo-ai-alt-text-creator' ); ?></span>
		</label>
		<?php
	}

	public function render_cron_batch_field() {
		$options = $this->get_options();
		$val     = isset( $options['cron_batch_size'] ) ? $options['cron_batch_size'] : 2;
		?>
		<div class="aialtg-input-group">
			<input type="number" name="<?php echo esc_attr( self::$option_name . '[cron_batch_size]' ); ?>" value="<?php echo esc_attr( $val ); ?>" class="small-text" min="1" />
			<span class="aialtg-suffix"><?php esc_html_e( 'images per run', 'kookoo-ai-alt-text-creator' ); ?></span>
		</div>
		<p class="description">
			<?php esc_html_e( 'Recommended: 1 to 5 images per run to avoid server timeouts.', 'kookoo-ai-alt-text-creator' ); ?>
		</p>
		<?php
	}

	public function render_cron_interval_field() {
		$options = $this->get_options();
		$val     = isset( $options['cron_interval'] ) ? $options['cron_interval'] : 5;
		?>
		<div class="aialtg-input-group">
			<input type="number" name="<?php echo esc_attr( self::$option_name . '[cron_interval]' ); ?>" value="<?php echo esc_attr( $val ); ?>" class="small-text" min="1" />
			<span class="aialtg-suffix"><?php esc_html_e( 'minutes', 'kookoo-ai-alt-text-creator' ); ?></span>
		</div>
		<p class="description">
			<?php esc_html_e( 'Define the background schedule frequency (minimum 1 minute).', 'kookoo-ai-alt-text-creator' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders upgrade card to purchase Pro version.
	 */


	private function render_section_by_id( $section_id ) {
		global $wp_settings_sections, $wp_settings_fields;
		$page = 'kookoo-ai-alt-text-creator';
		if ( ! isset( $wp_settings_sections[ $page ][ $section_id ] ) ) {
			return;
		}
		$section = $wp_settings_sections[ $page ][ $section_id ];
		if ( $section['title'] ) {
			echo '<h2>' . esc_html( $section['title'] ) . "</h2>\n";
		}
		if ( $section['callback'] ) {
			call_user_func( $section['callback'], $section );
		}
		if ( isset( $wp_settings_fields[ $page ][ $section_id ] ) ) {
			echo '<table class="form-table" role="presentation">';
			do_settings_fields( $page, $section_id );
			echo '</table>';
		}
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap aialtg-wrapper">
			<div class="aialtg-header">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<p class="aialtg-subtitle"><?php esc_html_e( 'Automate your Image SEO with AI', 'kookoo-ai-alt-text-creator' ); ?></p>
			</div>

			<div class="aialtg-dashboard-layout">
				<div class="aialtg-main-content">
					<form action="options.php" method="post" class="aialtg-main-form" novalidate>
						<?php
						settings_fields( 'aialtg_plugin_options' );

						$show_pro_tab = apply_filters( 'aialtg_show_pro_tab', true );

						echo '<div class="aialtg-tabs-nav">';
						echo '<button type="button" class="aialtg-tab-btn active" data-tab="api">';
						echo '<span class="dashicons dashicons-admin-network"></span> ';
						echo esc_html__( 'General', 'kookoo-ai-alt-text-creator' );
						echo '</button>';
						echo '<button type="button" class="aialtg-tab-btn" data-tab="options">';
						echo '<span class="dashicons dashicons-admin-generic"></span> ';
						echo esc_html__( 'Generation Options', 'kookoo-ai-alt-text-creator' );
						echo '</button>';
						echo '<button type="button" class="aialtg-tab-btn" data-tab="cron">';
						echo '<span class="dashicons dashicons-backup"></span> ';
						echo esc_html__( 'Bulk Generation', 'kookoo-ai-alt-text-creator' );
						echo '</button>';
						if ( $show_pro_tab ) {
							echo '<button type="button" class="aialtg-tab-btn aialtg-tab-btn-pro" data-tab="pro">';
							echo '<span class="dashicons dashicons-star-filled"></span> ';
							echo esc_html__( 'Pro Features', 'kookoo-ai-alt-text-creator' );
							echo '</button>';
						}
						echo '<button type="button" class="aialtg-tab-btn" data-tab="help">';
						echo '<span class="dashicons dashicons-editor-help"></span> ';
						echo esc_html__( 'Help', 'kookoo-ai-alt-text-creator' );
						echo '</button>';
						echo '</div>';

						echo '<div id="aialtg-tab-api" class="aialtg-tab-panel active">';
						$this->render_section_by_id( 'aialtg_api_section' );
						echo '</div>';

						echo '<div id="aialtg-tab-options" class="aialtg-tab-panel">';
						$this->render_section_by_id( 'aialtg_options_section' );
						echo '</div>';

						echo '<div id="aialtg-tab-cron" class="aialtg-tab-panel">';
						$this->render_section_by_id( 'aialtg_cron_section' );
						echo '</div>';

						if ( $show_pro_tab ) {
							echo '<div id="aialtg-tab-pro" class="aialtg-tab-panel">';
							$this->render_pro_preview_tab();
							echo '</div>';
						}

						echo '<div id="aialtg-tab-help" class="aialtg-tab-panel">';
						?>
						<h2><?php esc_html_e( 'Quick Help & Resources', 'kookoo-ai-alt-text-creator' ); ?></h2>
						<p class="aialtg-section-desc"><?php esc_html_e( 'Learn how the plugin works, how to get API keys for each provider, and find troubleshooting guidance.', 'kookoo-ai-alt-text-creator' ); ?></p>
						<div class="form-table aialtg-help-table">
							<div class="aialtg-help-content">
								<div class="aialtg-help-section">
									<h3><?php esc_html_e( 'How It Works', 'kookoo-ai-alt-text-creator' ); ?></h3>
									<ol class="aialtg-help-steps">
										<li><?php esc_html_e( 'Choose your preferred AI Gateway, obtain an API key, and select an AI vision model.', 'kookoo-ai-alt-text-creator' ); ?></li>
										<li><?php esc_html_e( 'Configure your Alt Text and Title prompt instructions to match your SEO and accessibility needs.', 'kookoo-ai-alt-text-creator' ); ?></li>
										<li><?php esc_html_e( 'Generate alt texts individually in the Media Library or configure background bulk generation for your catalog.', 'kookoo-ai-alt-text-creator' ); ?></li>
									</ol>
								</div>

								<div class="aialtg-help-section">
									<h3><?php esc_html_e( 'How to Get Your API Key', 'kookoo-ai-alt-text-creator' ); ?></h3>
									<p class="aialtg-section-desc" style="margin-bottom: 16px;">
										<?php esc_html_e( 'Follow the instructions below to generate your OpenRouter API key:', 'kookoo-ai-alt-text-creator' ); ?>
									</p>

									<div class="aialtg-gateway-guides-grid">
										<!-- OpenRouter Guide Card -->
										<div class="aialtg-gateway-guide-card">
											<div class="aialtg-gateway-guide-header">
												<div class="aialtg-gateway-guide-title-wrap">
													<span class="dashicons dashicons-rest-api aialtg-gateway-icon"></span>
													<h4><?php esc_html_e( 'OpenRouter', 'kookoo-ai-alt-text-creator' ); ?></h4>
												</div>
											</div>
											<p class="aialtg-gateway-guide-desc">
												<?php esc_html_e( 'Aggregator gateway providing access to hundreds of models (Claude, GPT, Gemini, Llama, and free community models).', 'kookoo-ai-alt-text-creator' ); ?>
											</p>
											<ol class="aialtg-gateway-steps">
												<li>
													<?php
													echo wp_kses_post(
														sprintf(
															/* translators: %s: OpenRouter keys URL */
															__( 'Go to <a href="%s" target="_blank" rel="noopener noreferrer">openrouter.ai/keys</a> and log in or create an account.', 'kookoo-ai-alt-text-creator' ),
															'https://openrouter.ai/keys'
														)
													);
													?>
												</li>
												<li><?php esc_html_e( 'Click "Create Key", label it (e.g. "KooKoo Alt Text"), and copy the generated key (starts with "sk-or-v1-").', 'kookoo-ai-alt-text-creator' ); ?></li>
												<li><?php esc_html_e( 'Add credits under Account if using paid models, or choose models with the ":free" suffix to test at zero cost.', 'kookoo-ai-alt-text-creator' ); ?></li>
												<li><?php esc_html_e( 'In the General tab, enter your key, click "Test Connection", and save your settings.', 'kookoo-ai-alt-text-creator' ); ?></li>
											</ol>
											<div class="aialtg-gateway-guide-action">
												<a href="https://openrouter.ai/keys" target="_blank" rel="noopener noreferrer" class="button button-secondary">
													<span class="dashicons dashicons-external"></span>
													<span><?php esc_html_e( 'Get OpenRouter Key', 'kookoo-ai-alt-text-creator' ); ?></span>
												</a>
											</div>
										</div>
									</div>
								</div>

								<?php
								// Allow extensions (e.g. Pro Addon) to output documentation sections.
								do_action( 'aialtg_settings_help_sections' );
								?>

								<div class="aialtg-help-section aialtg-help-troubleshooting">
									<h3><?php esc_html_e( 'Troubleshooting & Common Questions', 'kookoo-ai-alt-text-creator' ); ?></h3>
									<ul class="aialtg-help-tips">
										<li>
											<strong><?php esc_html_e( 'HTTP 401 Unauthorized:', 'kookoo-ai-alt-text-creator' ); ?></strong>
											<?php esc_html_e( 'Check for leading or trailing whitespace when copying your API key.', 'kookoo-ai-alt-text-creator' ); ?>
										</li>
										<li>
											<strong><?php esc_html_e( 'HTTP 402 or 429 Quota Exceeded:', 'kookoo-ai-alt-text-creator' ); ?></strong>
											<?php esc_html_e( 'Check your account credit balance with OpenRouter under Account > Credits if you are using paid models.', 'kookoo-ai-alt-text-creator' ); ?>
										</li>
										<li>
											<strong><?php esc_html_e( 'Localhost / Private Network Compatibility:', 'kookoo-ai-alt-text-creator' ); ?></strong>
											<?php esc_html_e( 'The plugin automatically detects local environments and transmits images as Base64 data, ensuring seamless testing without needing a public domain.', 'kookoo-ai-alt-text-creator' ); ?>
										</li>
									</ul>
								</div>
							</div>
						</div>
						<?php
						echo '</div>';

						echo '<div class="aialtg-submit-wrap">';
						submit_button( __( 'Save Settings', 'kookoo-ai-alt-text-creator' ), 'primary large' );
						echo '</div>';
						?>
					</form>
				</div>
				<div class="aialtg-sidebar">
					<?php $this->render_pro_card(); ?>
					<?php $this->render_stats_card(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders Pro feature preview showcase tab for free users.
	 */
	public function render_pro_preview_tab() {
		?>
		<div class="aialtg-pro-showcase">
			<div class="aialtg-pro-hero">
				<h2><?php echo esc_html__( 'Take Your Image SEO Further with Pro', 'kookoo-ai-alt-text-creator' ); ?></h2>
				<p class="aialtg-pro-lead">
					<?php echo esc_html__( 'The KooKoo AI Alt Text Creator Pro Addon unlocks direct API gateways, complete metadata generation, instant upload automation, and server-level WP-CLI control.', 'kookoo-ai-alt-text-creator' ); ?>
				</p>
			</div>

			<div class="aialtg-pro-features-grid">
				<div class="aialtg-pro-feature-card">
					<div class="aialtg-pro-feature-icon">
						<span class="dashicons dashicons-admin-network"></span>
					</div>
					<h3><?php echo esc_html__( 'Direct OpenAI & Google Gemini Gateways', 'kookoo-ai-alt-text-creator' ); ?></h3>
					<p><?php echo esc_html__( 'Connect directly to your OpenAI (GPT-4o, GPT-4o-mini) or Google AI Studio (Gemini 2.5 Flash, Gemini 2.5 Pro) accounts using your own API keys for lower latency.', 'kookoo-ai-alt-text-creator' ); ?></p>
				</div>

				<div class="aialtg-pro-feature-card">
					<div class="aialtg-pro-feature-icon">
						<span class="dashicons dashicons-media-document"></span>
					</div>
					<h3><?php echo esc_html__( 'Generate Captions & Descriptions', 'kookoo-ai-alt-text-creator' ); ?></h3>
					<p><?php echo esc_html__( 'Go beyond alt text and titles. Automatically generate engaging captions and detailed, accessible image descriptions with custom prompts.', 'kookoo-ai-alt-text-creator' ); ?></p>
				</div>

				<div class="aialtg-pro-feature-card">
					<div class="aialtg-pro-feature-icon">
						<span class="dashicons dashicons-upload"></span>
					</div>
					<h3><?php echo esc_html__( 'Auto-Generate on Upload', 'kookoo-ai-alt-text-creator' ); ?></h3>
					<p><?php echo esc_html__( 'Hands-free automation. The instant an author uploads a new image anywhere in WordPress, AI generates and fills all enabled fields immediately.', 'kookoo-ai-alt-text-creator' ); ?></p>
				</div>

				<div class="aialtg-pro-feature-card">
					<div class="aialtg-pro-feature-icon">
						<span class="dashicons dashicons-images-alt2"></span>
					</div>
					<h3><?php echo esc_html__( 'Media Library Bulk Actions', 'kookoo-ai-alt-text-creator' ); ?></h3>
					<p><?php echo esc_html__( 'Select multiple images in the WordPress Media Library list view and click "Generate AI Details" to process your selection on demand.', 'kookoo-ai-alt-text-creator' ); ?></p>
				</div>

				<div class="aialtg-pro-feature-card">
					<div class="aialtg-pro-feature-icon">
						<span class="dashicons dashicons-shield"></span>
					</div>
					<h3><?php echo esc_html__( 'Granular Overwrite Protection', 'kookoo-ai-alt-text-creator' ); ?></h3>
					<p><?php echo esc_html__( 'Preserve manual human edits. The plugin checks each metadata field individually and only generates missing values, skipping complete images.', 'kookoo-ai-alt-text-creator' ); ?></p>
				</div>

				<div class="aialtg-pro-feature-card">
					<div class="aialtg-pro-feature-icon">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<rect x="2" y="3" width="20" height="18" rx="3"></rect>
							<polyline points="7 9 10 12 7 15"></polyline>
							<line x1="13" y1="15" x2="17" y2="15"></line>
						</svg>
					</div>
					<h3><?php echo esc_html__( 'WP-CLI Command Line Tool', 'kookoo-ai-alt-text-creator' ); ?></h3>
					<p><?php echo esc_html__( 'Manage heavy generation workloads via terminal or server cron with the native "wp kookoo-alt-text process" command.', 'kookoo-ai-alt-text-creator' ); ?></p>
				</div>
			</div>

			<div class="aialtg-pro-cta-banner">
				<div class="aialtg-pro-cta-text">
					<h3><?php echo esc_html__( 'Ready to upgrade your media workflow?', 'kookoo-ai-alt-text-creator' ); ?></h3>
					<p><?php echo esc_html__( 'Install the Pro Addon alongside this free version to unlock all premium capabilities.', 'kookoo-ai-alt-text-creator' ); ?></p>
				</div>
				<div class="aialtg-pro-cta-action">
					<a href="https://violo.ir/?p=14" target="_blank" rel="noopener noreferrer" class="button button-primary button-hero">
						<span><?php echo esc_html__( 'Get Pro Addon', 'kookoo-ai-alt-text-creator' ); ?></span>
						<span class="dashicons dashicons-external"></span>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}
}