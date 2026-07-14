<?php
/**
 * EDD Software Licensing Manager Class
 *
 * @package AI_Alt_Text_Generator
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Aialtg_Licensing' ) ) {
/**
 * Handles EDD Software Licensing operations.
 */
class Aialtg_Licensing {

	/**
	 * EDD Store URL.
	 */
	const STORE_URL = 'https://violo.ir/';

	/**
	 * EDD Download (Item) ID.
	 */
	const ITEM_ID = 14;

	/**
	 * Option key for plugin settings.
	 */
	private $option_name = 'aialtg_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// AJAX Handlers.
		add_action( 'wp_ajax_aialtg_activate_license', array( $this, 'ajax_activate_license' ) );
		add_action( 'wp_ajax_aialtg_deactivate_license', array( $this, 'ajax_deactivate_license' ) );

		// Admin hooks for background verification check.
		add_action( 'admin_init', array( $this, 'maybe_check_license' ) );
	}

	/**
	 * Remote API request helper.
	 *
	 * @param string $action      EDD action ('activate_license', 'deactivate_license', 'check_license').
	 * @param string $license_key License key.
	 * @return object|WP_Error Response object or WP_Error.
	 */
	public static function perform_edd_request( $action, $license_key ) {
		$api_params = array(
			'edd_action' => sanitize_text_field( $action ),
			'license'    => sanitize_text_field( $license_key ),
			'item_id'    => self::ITEM_ID,
			'url'        => home_url(),
		);

		$url = add_query_arg( $api_params, self::STORE_URL );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			return new WP_Error(
				'edd_api_http_error',
				/* translators: %d: HTTP status code */
				sprintf( __( 'EDD Store returned HTTP %d', 'kookoo-ai-alt-text-creator' ), $response_code )
			);
		}

		$data = json_decode( $body );

		if ( ! is_object( $data ) ) {
			return new WP_Error( 'edd_api_invalid_json', __( 'Invalid response format from EDD Store', 'kookoo-ai-alt-text-creator' ) );
		}

		return $data;
	}

	/**
	 * Perform remote deactivation of a key.
	 *
	 * @param string $license_key License key.
	 * @return bool True if successful, false otherwise.
	 */
	public static function deactivate_license( $license_key ) {
		$result = self::perform_edd_request( 'deactivate_license', $license_key );
		if ( is_wp_error( $result ) ) {
			return false;
		}
		return isset( $result->license ) && 'deactivated' === $result->license;
	}

	/**
	 * AJAX handler to activate a license.
	 */
	public function ajax_activate_license() {
		try {
			if ( ! isset( $_POST['nonce'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Nonce missing', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aialtg_license_nonce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'kookoo-ai-alt-text-creator' ) ) );
			}

			$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
			$license_key = trim( $license_key );

			if ( empty( $license_key ) ) {
				wp_send_json_error( array( 'message' => __( 'Please enter a license key.', 'kookoo-ai-alt-text-creator' ) ) );
			}

			$options = get_option( $this->option_name );
			if ( ! is_array( $options ) ) {
				$options = array();
			}

			$response = self::perform_edd_request( 'activate_license', $license_key );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			}

			if ( isset( $response->success ) && $response->success ) {
				$options['license_key']    = $license_key;
				$options['license_status'] = 'valid';
				$options['license_data']   = array(
					'expiry'         => isset( $response->expires ) ? $response->expires : '',
					'customer_email' => isset( $response->customer_email ) ? $response->customer_email : '',
					'license_limit'  => isset( $response->license_limit ) ? $response->license_limit : 0,
					'site_count'     => isset( $response->site_count ) ? $response->site_count : 0,
				);

				update_option( $this->option_name, $options );
				set_transient( 'aialtg_license_check_lock', '1', DAY_IN_SECONDS );

				$expiry_text = ! empty( $response->expires ) ? date_i18n( get_option( 'date_format' ), strtotime( $response->expires ) ) : __( 'Lifetime', 'kookoo-ai-alt-text-creator' );

				wp_send_json_success(
					array(
						'message'     => __( 'License activated successfully!', 'kookoo-ai-alt-text-creator' ),
						'status'      => 'valid',
						'expiry_text' => $expiry_text,
					)
				);
			} else {
				$options['license_key']    = $license_key;
				$options['license_status'] = isset( $response->error ) ? $response->error : 'invalid';
				$options['license_data']   = array();
				update_option( $this->option_name, $options );
				delete_transient( 'aialtg_license_check_lock' );

				$error_message = $this->get_edd_error_message( $response );
				wp_send_json_error( array( 'message' => $error_message ) );
			}
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler to deactivate a license.
	 */
	public function ajax_deactivate_license() {
		try {
			if ( ! isset( $_POST['nonce'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Nonce missing', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aialtg_license_nonce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed', 'kookoo-ai-alt-text-creator' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'kookoo-ai-alt-text-creator' ) ) );
			}

			$options = get_option( $this->option_name );
			if ( ! is_array( $options ) ) {
				$options = array();
			}

			$license_key = isset( $options['license_key'] ) ? $options['license_key'] : '';

			if ( ! empty( $license_key ) ) {
				self::deactivate_license( $license_key );
			}

			$options['license_status'] = 'inactive';
			$options['license_data']   = array();
			update_option( $this->option_name, $options );
			delete_transient( 'aialtg_license_check_lock' );

			wp_send_json_success( array( 'message' => __( 'License deactivated successfully.', 'kookoo-ai-alt-text-creator' ) ) );
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Translates EDD license errors into user-friendly messages.
	 *
	 * @param object $response Response object.
	 * @return string Error message.
	 */
	private function get_edd_error_message( $response ) {
		$error = isset( $response->error ) ? $response->error : '';
		switch ( $error ) {
			case 'expired':
				return __( 'Your license key has expired.', 'kookoo-ai-alt-text-creator' );
			case 'revoked':
				return __( 'Your license key has been revoked.', 'kookoo-ai-alt-text-creator' );
			case 'missing':
				return __( 'License key is missing or invalid.', 'kookoo-ai-alt-text-creator' );
			case 'no_activations_left':
				return __( 'Your license key has reached its activation limit.', 'kookoo-ai-alt-text-creator' );
			case 'license_not_activatable':
				return __( 'This license key cannot be activated.', 'kookoo-ai-alt-text-creator' );
			case 'invalid_item_id':
				return __( 'This license key does not match this product.', 'kookoo-ai-alt-text-creator' );
			case 'item_name_mismatch':
				return __( 'Product name mismatch.', 'kookoo-ai-alt-text-creator' );
			case 'site_inactive':
				return __( 'This site is not active on this license.', 'kookoo-ai-alt-text-creator' );
			default:
				return __( 'An error occurred while activating your license.', 'kookoo-ai-alt-text-creator' );
		}
	}

	/**
	 * Periodically verifies the license key on admin init.
	 */
	public function maybe_check_license() {
		// Only check on admin requests.
		if ( ! is_admin() ) {
			return;
		}

		// Only check if we are on our settings page to prevent checking on every admin page load.
		$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page           = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'kookoo-ai-alt-text-creator' !== $page ) {
			return;
		}

		$options = get_option( $this->option_name );
		if ( ! is_array( $options ) ) {
			return;
		}

		$license_key    = isset( $options['license_key'] ) ? $options['license_key'] : '';
		$license_status = isset( $options['license_status'] ) ? $options['license_status'] : 'inactive';

		if ( empty( $license_key ) || 'valid' !== $license_status ) {
			return;
		}

		// Check lock transient to limit checks to once every 24 hours.
		if ( false !== get_transient( 'aialtg_license_check_lock' ) ) {
			return;
		}

		// Run the remote check.
		$response = self::perform_edd_request( 'check_license', $license_key );

		if ( is_wp_error( $response ) ) {
			// Fail-safe: if store is down, do not lock user out. Cache status and try again in 12 hours.
			set_transient( 'aialtg_license_check_lock', '1', 12 * HOUR_IN_SECONDS );
			return;
		}

		if ( isset( $response->license ) ) {
			$options['license_status'] = $response->license;
			if ( 'valid' === $response->license ) {
				$options['license_data'] = array(
					'expiry'         => isset( $response->expires ) ? $response->expires : '',
					'customer_email' => isset( $response->customer_email ) ? $response->customer_email : '',
					'license_limit'  => isset( $response->license_limit ) ? $response->license_limit : 0,
					'site_count'     => isset( $response->site_count ) ? $response->site_count : 0,
				);
			} else {
				$options['license_data'] = array();
			}
			update_option( $this->option_name, $options );
		}

		// Lock check for 24 hours.
		set_transient( 'aialtg_license_check_lock', '1', DAY_IN_SECONDS );
	}
}
}
