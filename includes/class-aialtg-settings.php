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

		add_settings_field(
			'save_gen_meta',
			__( 'Save Generation Info', 'kookoo-ai-alt-text-creator' ),
			array( $this, 'render_save_gen_meta_field' ),
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
	}

	/**
	 * Sanitizes setting inputs.
	 */
	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		$new_input = array();

		// License Settings (Removed)

		// API Settings.
		if ( isset( $input['api_key'] ) ) {
			$new_input['api_key'] = sanitize_text_field( $input['api_key'] );
		}
		if ( isset( $input['model'] ) ) {
			$new_input['model'] = sanitize_text_field( $input['model'] );
		}

		// Feature Toggles.
		$new_input['enable_alt']    = isset( $input['enable_alt'] ) ? '1' : '0';
		$new_input['enable_title']  = isset( $input['enable_title'] ) ? '1' : '0';
		$new_input['save_gen_meta'] = isset( $input['save_gen_meta'] ) ? '1' : '0';

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
		$new_input['cron_enabled']    = isset( $input['cron_enabled'] ) ? '1' : '0';
		$new_input['cron_batch_size'] = isset( $input['cron_batch_size'] ) ? absint( $input['cron_batch_size'] ) : 1;
		$new_input['cron_interval']   = isset( $input['cron_interval'] ) ? absint( $input['cron_interval'] ) : 5;

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

		if ( is_array( $allowed_mimes ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $allowed_mimes ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$mime_where = $wpdb->prepare( "p.post_mime_type IN ($placeholders)", $allowed_mimes );
		} else {
			$mime_where = $wpdb->prepare( 'p.post_mime_type LIKE %s', $allowed_mimes . '/%' );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results( "
			SELECT pm.meta_value as status, COUNT(p.ID) as count
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_aialtg_processed'
			WHERE p.post_type = 'attachment' AND p.post_status = 'inherit' AND {$mime_where}
			GROUP BY pm.meta_value
		" );
		// phpcs:enable

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

	public function render_api_key_field() {
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();
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
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();
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
				<div class="aialtg-custom-model-input-wrap" style="display: none;">
					<input type="text" id="aialtg-custom-model-input" class="regular-text" placeholder="e.g. google/gemini-2.5-flash-lite" />
				</div>
				<!-- Hidden real input that holds the actual submitted value -->
				<input type="hidden" name="<?php echo esc_attr( self::$option_name . '[model]' ); ?>" id="aialtg-model-real-input" value="<?php echo esc_attr( $value ); ?>" />
				<p class="description"><?php esc_html_e( 'Select the language model to use.', 'kookoo-ai-alt-text-creator' ); ?></p>
			</div>
		</div>
		<?php
	}

	public function render_global_context_field() {
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();
		$val     = isset( $options['global_context'] ) ? $options['global_context'] : '';
		?>
		<div class="aialtg-input-wrap">
			<textarea name="<?php echo esc_attr( self::$option_name . '[global_context]' ); ?>" rows="3" cols="50" class="large-text code"><?php echo esc_textarea( $val ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Global instructions included in all requests. Use {post_title} and {post_content} variables here.', 'kookoo-ai-alt-text-creator' ); ?></p>
		</div>
		<?php
	}

	public function render_allowed_formats_field() {
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();
		$val     = isset( $options['allowed_formats'] ) ? $options['allowed_formats'] : 'jpg,jpeg,png,webp';
		?>
		<div class="aialtg-input-wrap">
			<input type="text" name="<?php echo esc_attr( self::$option_name . '[allowed_formats]' ); ?>" value="<?php echo esc_attr( $val ); ?>" class="large-text" />
			<p class="description"><?php esc_html_e( 'Comma-separated list (e.g., jpg, png, webp).', 'kookoo-ai-alt-text-creator' ); ?></p>
		</div>
		<?php
	}

	public function render_enable_alt_field() {
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();
		?>
		<label class="aialtg-toggle">
			<input type="checkbox" name="<?php echo esc_attr( self::$option_name . '[enable_alt]' ); ?>" value="1" <?php checked( isset( $options['enable_alt'] ) ? $options['enable_alt'] : '1', '1' ); ?> />
			<span class="aialtg-toggle-slider"></span>
			<span class="aialtg-toggle-label"><?php esc_html_e( 'Enable Alt Text', 'kookoo-ai-alt-text-creator' ); ?></span>
		</label>
		<?php
	}

	public function render_alt_prompt_field() {
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();
		$val     = isset( $options['prompt'] ) ? $options['prompt'] : 'Generate a concise, descriptive alt text...';
		?>
		<div class="aialtg-input-wrap">
			<textarea name="<?php echo esc_attr( self::$option_name . '[prompt]' ); ?>" rows="3" cols="50" class="large-text code"><?php echo esc_textarea( $val ); ?></textarea>
		</div>
		<?php
	}

	public function render_enable_title_field() {
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();
		?>
		<label class="aialtg-toggle">
			<input type="checkbox" name="<?php echo esc_attr( self::$option_name . '[enable_title]' ); ?>" value="1" <?php checked( isset( $options['enable_title'] ) ? $options['enable_title'] : '1', '1' ); ?> />
			<span class="aialtg-toggle-slider"></span>
			<span class="aialtg-toggle-label"><?php esc_html_e( 'Enable Title', 'kookoo-ai-alt-text-creator' ); ?></span>
		</label>
		<?php
	}

	public function render_title_prompt_field() {
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();
		$val     = isset( $options['title_prompt'] ) ? $options['title_prompt'] : 'Generate a short title...';
		?>
		<div class="aialtg-input-wrap">
			<textarea name="<?php echo esc_attr( self::$option_name . '[title_prompt]' ); ?>" rows="2" cols="50" class="large-text code"><?php echo esc_textarea( $val ); ?></textarea>
		</div>
		<?php
	}

	public function render_save_gen_meta_field() {
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();
		?>
		<label class="aialtg-toggle">
			<input type="checkbox" name="<?php echo esc_attr( self::$option_name . '[save_gen_meta]' ); ?>" value="1" <?php checked( isset( $options['save_gen_meta'] ) ? $options['save_gen_meta'] : '0', '1' ); ?> />
			<span class="aialtg-toggle-slider"></span>
			<span class="aialtg-toggle-label">
				<?php esc_html_e( 'Save generation metadata (timestamp/source)', 'kookoo-ai-alt-text-creator' ); ?>
			</span>
		</label>
		<?php
	}

	public function render_cron_enabled_field() {
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();

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
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();
		$val     = isset( $options['cron_batch_size'] ) ? $options['cron_batch_size'] : 1;
		?>
		<div class="aialtg-input-group">
			<input type="number" name="<?php echo esc_attr( self::$option_name . '[cron_batch_size]' ); ?>" value="<?php echo esc_attr( $val ); ?>" class="small-text" min="1" max="10" />
			<span class="aialtg-suffix"><?php esc_html_e( 'images per run', 'kookoo-ai-alt-text-creator' ); ?></span>
		</div>
		<p class="description"><?php esc_html_e( 'Keep this low (1-3) to avoid server timeouts.', 'kookoo-ai-alt-text-creator' ); ?></p>
		<?php
	}

	public function render_cron_interval_field() {
		$options = get_option( self::$option_name );
		$options = ( is_array( $options ) ) ? $options : array();
		$val     = isset( $options['cron_interval'] ) ? $options['cron_interval'] : 5;
		?>
		<div class="aialtg-input-group">
			<input type="number" name="<?php echo esc_attr( self::$option_name . '[cron_interval]' ); ?>" value="<?php echo esc_attr( $val ); ?>" class="small-text" min="1" />
			<span class="aialtg-suffix"><?php esc_html_e( 'minutes', 'kookoo-ai-alt-text-creator' ); ?></span>
		</div>
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
					<form action="options.php" method="post" class="aialtg-main-form">
						<?php
						settings_fields( 'aialtg_plugin_options' );

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

						echo '<div id="aialtg-tab-help" class="aialtg-tab-panel">';
						?>
						<h2><?php esc_html_e( 'Quick Help & Resources', 'kookoo-ai-alt-text-creator' ); ?></h2>
						<p class="aialtg-section-desc"><?php esc_html_e( 'Learn how the plugin works and find support resources.', 'kookoo-ai-alt-text-creator' ); ?></p>
						<div class="form-table aialtg-help-table">
							<div class="aialtg-help-content">
								<p><strong><?php esc_html_e( 'How it works:', 'kookoo-ai-alt-text-creator' ); ?></strong></p>
								<ol>
									<li><?php esc_html_e( 'Enter your OpenRouter API Key and select a model (e.g. Gemini 2.5 Flash).', 'kookoo-ai-alt-text-creator' ); ?></li>
									<li><?php esc_html_e( 'Configure your Alt Text & Title prompt guidelines.', 'kookoo-ai-alt-text-creator' ); ?></li>
									<li><?php esc_html_e( 'Background processing will automatically analyze pending images.', 'kookoo-ai-alt-text-creator' ); ?></li>
								</ol>
								<p class="description"><?php esc_html_e( 'Need support or want to read documentation? Visit the official plugin directory page.', 'kookoo-ai-alt-text-creator' ); ?></p>
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
					<?php $this->render_stats_card(); ?>
				</div>
			</div>
		</div>
		<?php
	}
}
}