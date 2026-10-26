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
 * Handles plugin settings, admin menu, and options dashboard.
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
	 * Registers plugin settings, sections, and fields.
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
	}

	/**
	 * Sanitizes setting inputs.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized input.
	 */
	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		$new_input = array();

		$old_options = get_option( self::$option_name );
		$old_options = is_array( $old_options ) ? $old_options : array();

		// API Settings.
		$allowed_gateways = apply_filters( 'aialtg_allowed_gateways', array( 'openrouter' ) );
		$gateway          = isset( $input['api_gateway'] ) ? sanitize_text_field( $input['api_gateway'] ) : 'openrouter';
		if ( ! in_array( $gateway, $allowed_gateways, true ) ) {
			$gateway = 'openrouter';
		}
		$new_input['api_gateway'] = $gateway;

		if ( isset( $input['api_key'] ) ) {
			$new_input['api_key'] = sanitize_text_field( $input['api_key'] );
		}

		if ( isset( $input['model'] ) ) {
			$new_input['model'] = sanitize_text_field( $input['model'] );
		}

		// Feature Toggles.
		$new_input['enable_alt']   = isset( $input['enable_alt'] ) ? '1' : '0';
		$new_input['enable_title'] = isset( $input['enable_title'] ) ? '1' : '0';
		$new_input['save_gen_meta'] = '1';

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

		$cron_hook    = Aialtg_Cron::CRON_HOOK;
		$is_scheduled = wp_next_scheduled( $cron_hook );

		if ( '0' === $new_input['cron_enabled'] ) {
			if ( $is_scheduled ) {
				wp_clear_scheduled_hook( $cron_hook );
			}
		} elseif ( '1' === $new_input['cron_enabled'] ) {
			if ( ! $is_scheduled || $needs_reschedule ) {
				wp_clear_scheduled_hook( $cron_hook );
				wp_schedule_event( time() + 10, Aialtg_Cron::INTERVAL_NAME, $cron_hook );
			}
		}

		$new_input = apply_filters( 'aialtg_sanitize_settings', $new_input, $new_input, $old_options );

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
		$input = isset( $options['allowed_formats'] ) ? $options['allowed_formats'] : 'jpg,jpeg,png,webp';

		if ( empty( trim( $input ) ) ) {
			return 'image';
		}

		$exts     = array_map( 'trim', explode( ',', strtolower( $input ) ) );
		$mimes    = array();
		$wp_mimes = wp_get_mime_types();

		foreach ( $wp_mimes as $pattern => $type ) {
			$type_exts = explode( '|', $pattern );
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
			$placeholders = implode( ', ', array_fill( 0, count( $allowed_mimes ), '%s' ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total_images = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p WHERE p.post_type = 'attachment' AND p.post_mime_type IN ($placeholders)",
					$allowed_mimes
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$processed_images = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_aialtg_processed' AND pm.meta_value = '1') WHERE p.post_type = 'attachment' AND p.post_mime_type IN ($placeholders)",
					$allowed_mimes
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$failed_images = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_aialtg_processed' AND pm.meta_value = 'failed') WHERE p.post_type = 'attachment' AND p.post_mime_type IN ($placeholders)",
					$allowed_mimes
				)
			);
		} else {
			$image_like = $wpdb->esc_like( 'image/' ) . '%';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total_images = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE %s",
					$image_like
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$processed_images = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_aialtg_processed' AND pm.meta_value = '1') WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE %s",
					$image_like
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$failed_images = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_aialtg_processed' AND pm.meta_value = 'failed') WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE %s",
					$image_like
				)
			);
		}

		$pending_images = max( 0, $total_images - ( $processed_images + $failed_images ) );

		$stats = array(
			'total'     => $total_images,
			'processed' => $processed_images,
			'failed'    => $failed_images,
			'pending'   => $pending_images,
		);

		set_transient( 'aialtg_stats', $stats, HOUR_IN_SECONDS );

		return $stats;
	}

	/**
	 * Renders the Upgrade to Pro card in the sidebar.
	 */
	private function render_upgrade_pro_card() {
		$pro_url = 'https://violo.ir/?p=14';
		?>
		<div class="aialtg-card aialtg-pro-card">
			<div class="aialtg-pro-badge">
				<span class="aialtg-pro-star" aria-hidden="true">&#9733;</span>
				<span class="aialtg-pro-title"><?php esc_html_e( 'UPGRADE TO PRO', 'kookoo-ai-alt-text-creator' ); ?></span>
			</div>
			<p class="aialtg-pro-desc">
				<?php esc_html_e( 'Supercharge your media workflow with advanced automation, full metadata, and direct AI access:', 'kookoo-ai-alt-text-creator' ); ?>
			</p>
			<ul class="aialtg-pro-features">
				<li class="aialtg-pro-feature-item">
					<span class="aialtg-pro-check-icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="10" cy="10" r="10" fill="#a855f7"/>
							<path d="M6 10.2L8.7 13L14 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<div class="aialtg-pro-feature-text">
						<strong><?php esc_html_e( 'Direct API Gateways:', 'kookoo-ai-alt-text-creator' ); ?></strong>
						<span><?php esc_html_e( 'OpenAI & Google Gemini direct keys with zero platform fees', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
				</li>
				<li class="aialtg-pro-feature-item">
					<span class="aialtg-pro-check-icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="10" cy="10" r="10" fill="#a855f7"/>
							<path d="M6 10.2L8.7 13L14 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<div class="aialtg-pro-feature-text">
						<strong><?php esc_html_e( 'Complete 4-Field Metadata:', 'kookoo-ai-alt-text-creator' ); ?></strong>
						<span><?php esc_html_e( 'Captions & Descriptions alongside Alt & Titles with custom prompts', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
				</li>
				<li class="aialtg-pro-feature-item">
					<span class="aialtg-pro-check-icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="10" cy="10" r="10" fill="#a855f7"/>
							<path d="M6 10.2L8.7 13L14 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<div class="aialtg-pro-feature-text">
						<strong><?php esc_html_e( 'Instant Upload Automation:', 'kookoo-ai-alt-text-creator' ); ?></strong>
						<span><?php esc_html_e( 'Auto-generate metadata on new image uploads in real time', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
				</li>
				<li class="aialtg-pro-feature-item">
					<span class="aialtg-pro-check-icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="10" cy="10" r="10" fill="#a855f7"/>
							<path d="M6 10.2L8.7 13L14 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<div class="aialtg-pro-feature-text">
						<strong><?php esc_html_e( 'Media Library Bulk Actions:', 'kookoo-ai-alt-text-creator' ); ?></strong>
						<span><?php esc_html_e( 'Bulk process selected images with 1-click from the dropdown', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
				</li>
				<li class="aialtg-pro-feature-item">
					<span class="aialtg-pro-check-icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="10" cy="10" r="10" fill="#a855f7"/>
							<path d="M6 10.2L8.7 13L14 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<div class="aialtg-pro-feature-text">
						<strong><?php esc_html_e( 'Smart Overwrite Rules:', 'kookoo-ai-alt-text-creator' ); ?></strong>
						<span><?php esc_html_e( 'Skip existing fields to save API credits & protect human edits', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
				</li>
				<li class="aialtg-pro-feature-item">
					<span class="aialtg-pro-check-icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="10" cy="10" r="10" fill="#a855f7"/>
							<path d="M6 10.2L8.7 13L14 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<div class="aialtg-pro-feature-text">
						<strong><?php esc_html_e( 'Context-Aware AI:', 'kookoo-ai-alt-text-creator' ); ?></strong>
						<span><?php esc_html_e( 'Analyzes parent post/page title & content for tailored descriptions', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
				</li>
				<li class="aialtg-pro-feature-item">
					<span class="aialtg-pro-check-icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="10" cy="10" r="10" fill="#a855f7"/>
							<path d="M6 10.2L8.7 13L14 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<div class="aialtg-pro-feature-text">
						<strong><?php esc_html_e( 'Global Prompt Context:', 'kookoo-ai-alt-text-creator' ); ?></strong>
						<span><?php esc_html_e( 'Set universal brand guidelines & rules applied across all requests', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
				</li>
				<li class="aialtg-pro-feature-item">
					<span class="aialtg-pro-check-icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="10" cy="10" r="10" fill="#a855f7"/>
							<path d="M6 10.2L8.7 13L14 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<div class="aialtg-pro-feature-text">
						<strong><?php esc_html_e( 'Custom Vision Models:', 'kookoo-ai-alt-text-creator' ); ?></strong>
						<span><?php esc_html_e( 'Custom model slugs & dropdown presets for all gateways', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
				</li>
				<li class="aialtg-pro-feature-item">
					<span class="aialtg-pro-check-icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="10" cy="10" r="10" fill="#a855f7"/>
							<path d="M6 10.2L8.7 13L14 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<div class="aialtg-pro-feature-text">
						<strong><?php esc_html_e( 'Developer WP-CLI Tools:', 'kookoo-ai-alt-text-creator' ); ?></strong>
						<span><?php esc_html_e( 'Server terminal commands (wp kookoo-alt-text-pro) & crontab', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
				</li>
				<li class="aialtg-pro-feature-item">
					<span class="aialtg-pro-check-icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="10" cy="10" r="10" fill="#a855f7"/>
							<path d="M6 10.2L8.7 13L14 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<div class="aialtg-pro-feature-text">
						<strong><?php esc_html_e( 'Metadata Audit Trail:', 'kookoo-ai-alt-text-creator' ); ?></strong>
						<span><?php esc_html_e( 'Track execution sources (Upload, Bulk, Cron, Manual) on attachments', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
				</li>
			</ul>
			<div class="aialtg-pro-actions">
				<a href="<?php echo esc_url( $pro_url ); ?>" target="_blank" rel="noopener noreferrer" class="button aialtg-btn-pro-cta">
					<span><?php esc_html_e( 'Get Pro Addon Now', 'kookoo-ai-alt-text-creator' ); ?></span>
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
						<polyline points="15 3 21 3 21 9"></polyline>
						<line x1="10" y1="14" x2="21" y2="3"></line>
					</svg>
				</a>
				<a href="<?php echo esc_url( $pro_url ); ?>" target="_blank" rel="noopener noreferrer" class="aialtg-pro-preview-link">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
						<circle cx="12" cy="12" r="3"></circle>
					</svg>
					<span><?php esc_html_e( 'Preview Pro Features', 'kookoo-ai-alt-text-creator' ); ?></span>
				</a>
			</div>
			<div class="aialtg-pro-footer-note">
				<span class="dashicons dashicons-yes-alt"></span>
				<span><?php esc_html_e( 'Direct API Keys (No middleman markup) & Lifetime Updates', 'kookoo-ai-alt-text-creator' ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders image statistics card in sidebar.
	 */
	private function render_stats_card() {
		$stats = $this->get_image_stats();
		?>
		<div class="aialtg-card aialtg-stats-card">
			<div class="aialtg-card-header">
				<h3>
					<span class="dashicons dashicons-chart-pie"></span>
					<?php esc_html_e( 'Library Overview', 'kookoo-ai-alt-text-creator' ); ?>
				</h3>
			</div>
			<div class="aialtg-card-body">
				<div class="aialtg-stats-grid">
					<div class="aialtg-stat-item">
						<span class="aialtg-stat-value"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
						<span class="aialtg-stat-label"><?php esc_html_e( 'Supported Images', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
					<div class="aialtg-stat-item aialtg-stat-success">
						<span class="aialtg-stat-value"><?php echo esc_html( number_format_i18n( $stats['processed'] ) ); ?></span>
						<span class="aialtg-stat-label"><?php esc_html_e( 'Processed', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
					<div class="aialtg-stat-item aialtg-stat-warning">
						<span class="aialtg-stat-value"><?php echo esc_html( number_format_i18n( $stats['pending'] ) ); ?></span>
						<span class="aialtg-stat-label"><?php esc_html_e( 'Pending', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
					<div class="aialtg-stat-item aialtg-stat-error">
						<span class="aialtg-stat-value"><?php echo esc_html( number_format_i18n( $stats['failed'] ) ); ?></span>
						<span class="aialtg-stat-label"><?php esc_html_e( 'Failed', 'kookoo-ai-alt-text-creator' ); ?></span>
					</div>
				</div>
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
	 * Section description callbacks.
	 */
	public function render_cron_section_info() {
		echo '<p class="aialtg-section-desc">' . esc_html__( 'Configure the background scheduler to process images automatically.', 'kookoo-ai-alt-text-creator' ) . '</p>';
	}

	public function render_section_info() {
		echo '<p class="aialtg-section-desc">' . esc_html__( 'Choose your AI provider and enter your API credentials.', 'kookoo-ai-alt-text-creator' ) . '</p>';
	}

	public function render_options_section_info() {
		echo '<p class="aialtg-section-desc">' . esc_html__( 'Configure which metadata fields to generate and customize your AI prompt instructions.', 'kookoo-ai-alt-text-creator' ) . '</p>';
	}

	public function render_api_gateway_field() {
		$options  = $this->get_options();
		$gateways = apply_filters( 'aialtg_api_gateways', array(
			'openrouter' => __( 'OpenRouter (Default)', 'kookoo-ai-alt-text-creator' ),
		) );
		$gateway  = isset( $options['api_gateway'] ) && isset( $gateways[ $options['api_gateway'] ] ) ? $options['api_gateway'] : 'openrouter';
		?>
		<div class="aialtg-input-wrap">
			<select name="<?php echo esc_attr( self::$option_name . '[api_gateway]' ); ?>" id="aialtg-api-gateway" class="regular-text">
				<?php
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
			<div class="aialtg-skeleton-loader">
				<div class="aialtg-skeleton-input"></div>
			</div>

			<div class="aialtg-model-select-wrap" style="display: none;">
				<select id="aialtg-model-select" class="regular-text">
				</select>
			</div>

			<div class="aialtg-custom-model-input-wrap" style="display: none; margin-top: 8px;">
				<input type="text" id="aialtg-custom-model-input" class="regular-text" placeholder="e.g. google/gemini-2.5-flash-lite" />
				<p class="description"><?php esc_html_e( 'Enter any custom model ID supported by your chosen gateway.', 'kookoo-ai-alt-text-creator' ); ?></p>
			</div>

			<input type="hidden" name="<?php echo esc_attr( self::$option_name . '[model]' ); ?>" id="aialtg-model-real-input" value="<?php echo esc_attr( $value ); ?>" />
		</div>
		<?php
	}

	public function render_test_connection_field() {
		?>
		<div class="aialtg-test-connection-wrap">
			<button type="button" class="button button-secondary" id="aialtg-test-conn-btn" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aialtg_test_conn_nonce' ) ); ?>">
				<span class="dashicons dashicons-cloud"></span>
				<?php esc_html_e( 'Test API Connection', 'kookoo-ai-alt-text-creator' ); ?>
			</button>
			<span class="spinner aialtg-test-spinner" style="float: none; margin-top: 4px;"></span>
			<div id="aialtg-test-conn-result" style="margin-top: 10px; display: none;"></div>
		</div>
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
		</div>
		<p class="description">
			<?php esc_html_e( 'Custom instructions appended to every request. Use tags: {post_title}, {post_content}', 'kookoo-ai-alt-text-creator' ); ?>
		</p>
		<?php
	}

	public function render_allowed_formats_field() {
		$options = $this->get_options();
		$val     = isset( $options['allowed_formats'] ) ? $options['allowed_formats'] : 'jpg,jpeg,png,webp';
		?>
		<input type="text" name="<?php echo esc_attr( self::$option_name . '[allowed_formats]' ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'Comma-separated file extensions to process (e.g. jpg,jpeg,png,webp).', 'kookoo-ai-alt-text-creator' ); ?>
		</p>
		<?php
	}

	public function render_enable_alt_field() {
		$options = $this->get_options();
		$val     = isset( $options['enable_alt'] ) ? $options['enable_alt'] : '1';
		?>
		<label class="aialtg-toggle">
			<input type="checkbox" name="<?php echo esc_attr( self::$option_name . '[enable_alt]' ); ?>" value="1" <?php checked( $val, '1' ); ?> />
			<span class="aialtg-toggle-slider"></span>
			<span class="aialtg-toggle-label"><?php esc_html_e( 'Generate Alt Text', 'kookoo-ai-alt-text-creator' ); ?></span>
		</label>
		<?php
	}

	public function render_alt_prompt_field() {
		$options = $this->get_options();
		$default = 'Generate a concise, descriptive alt text for this image. Do not use phrases like "Image of".';
		$val     = isset( $options['prompt'] ) ? $options['prompt'] : $default;
		?>
		<div class="aialtg-input-wrap">
			<textarea name="<?php echo esc_attr( self::$option_name . '[prompt]' ); ?>" id="aialtg-alt-prompt" rows="2" cols="50" class="large-text code"><?php echo esc_textarea( $val ); ?></textarea>
			<div style="margin-top: 5px; text-align: right;">
				<button type="button" class="button button-link aialtg-reset-prompt-btn" style="font-size: 11px; padding: 0; min-height: 20px; line-height: 20px; text-decoration: none;" data-target="aialtg-alt-prompt" data-default="<?php echo esc_attr( $default ); ?>"><?php esc_html_e( 'Reset to Default', 'kookoo-ai-alt-text-creator' ); ?></button>
			</div>
		</div>
		<?php
	}

	public function render_enable_title_field() {
		$options = $this->get_options();
		$val     = isset( $options['enable_title'] ) ? $options['enable_title'] : '0';
		?>
		<label class="aialtg-toggle">
			<input type="checkbox" name="<?php echo esc_attr( self::$option_name . '[enable_title]' ); ?>" value="1" <?php checked( $val, '1' ); ?> />
			<span class="aialtg-toggle-slider"></span>
			<span class="aialtg-toggle-label"><?php esc_html_e( 'Generate Title', 'kookoo-ai-alt-text-creator' ); ?></span>
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
		$val     = isset( $options['cron_enabled'] ) ? $options['cron_enabled'] : '0';
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

	/**
	 * Renders the main dashboard and settings interface.
	 */
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
						echo esc_html__( 'Help & Docs', 'kookoo-ai-alt-text-creator' );
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
						$this->render_help_tab_content();
						echo '</div>';

						echo '<div class="aialtg-submit-wrap">';
						submit_button( __( 'Save Settings', 'kookoo-ai-alt-text-creator' ), 'primary large' );
						echo '</div>';
						?>
					</form>
				</div>
				<div class="aialtg-sidebar">
					<?php $this->render_upgrade_pro_card(); ?>
					<?php $this->render_stats_card(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the integrated documentation and setup guides in the Help tab.
	 */
	private function render_help_tab_content() {
		?>
		<h2><?php esc_html_e( 'Quick Help & Resources', 'kookoo-ai-alt-text-creator' ); ?></h2>
		<p class="aialtg-section-desc"><?php esc_html_e( 'Learn how the plugin works, how to get API keys for each provider, and find troubleshooting guidance.', 'kookoo-ai-alt-text-creator' ); ?></p>
		<div class="form-table aialtg-help-table">
			<div class="aialtg-help-content">
				<div class="aialtg-help-section">
					<h3><?php esc_html_e( 'How It Works', 'kookoo-ai-alt-text-creator' ); ?></h3>
					<ol class="aialtg-help-steps">
						<li><?php esc_html_e( 'Choose your preferred AI Gateway, obtain an API key, and select an AI vision model.', 'kookoo-ai-alt-text-creator' ); ?></li>
						<li><?php esc_html_e( 'Configure your prompt instructions for Alt Text and Titles.', 'kookoo-ai-alt-text-creator' ); ?></li>
						<li><?php esc_html_e( 'Generate details individually in the Media Library or configure automated background processing.', 'kookoo-ai-alt-text-creator' ); ?></li>
					</ol>
				</div>

				<div class="aialtg-help-section">
					<h3><?php esc_html_e( 'API Key Setup Guides', 'kookoo-ai-alt-text-creator' ); ?></h3>
					<p class="aialtg-section-desc" style="margin-bottom: 16px;">
						<?php esc_html_e( 'Follow the instructions below to obtain your personal API keys for each supported provider:', 'kookoo-ai-alt-text-creator' ); ?>
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
								<?php esc_html_e( 'Aggregator gateway providing access to hundreds of models (Claude, GPT, Gemini, Llama, and free models).', 'kookoo-ai-alt-text-creator' ); ?>
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
								<li><?php esc_html_e( 'Click "Create Key", label it, and copy the generated key (starts with "sk-or-v1-").', 'kookoo-ai-alt-text-creator' ); ?></li>
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

				<div class="aialtg-help-section">
					<h3><?php esc_html_e( 'Automation & Best Practices', 'kookoo-ai-alt-text-creator' ); ?></h3>
					<ul class="aialtg-help-tips">
						<li>
							<strong><?php esc_html_e( 'Background Processing (Cron):', 'kookoo-ai-alt-text-creator' ); ?></strong>
							<?php esc_html_e( 'Enable background processing to automatically generate Alt Texts and Titles for your entire library without keeping your browser open.', 'kookoo-ai-alt-text-creator' ); ?>
						</li>
					</ul>
				</div>

				<div class="aialtg-help-section aialtg-help-troubleshooting">
					<h3><?php esc_html_e( 'Troubleshooting & Common Questions', 'kookoo-ai-alt-text-creator' ); ?></h3>
					<ul class="aialtg-help-tips">
						<li>
							<strong><?php esc_html_e( 'HTTP 401 Unauthorized:', 'kookoo-ai-alt-text-creator' ); ?></strong>
							<?php esc_html_e( 'Check for leading or trailing whitespace when copying your API key.', 'kookoo-ai-alt-text-creator' ); ?>
						</li>
						<li>
							<strong><?php esc_html_e( 'HTTP 402 or 429 Quota Exceeded:', 'kookoo-ai-alt-text-creator' ); ?></strong>
							<?php esc_html_e( 'Check your account credit balance or quota limits with your provider.', 'kookoo-ai-alt-text-creator' ); ?>
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
	}
}
}
