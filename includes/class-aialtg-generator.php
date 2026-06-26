<?php
/**
 * Generator Class for AI Alt Text Creator
 * Handles API calls and data processing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Aialtg_Generator {

	private $option_name = 'aialtg_settings';

	/**
	 * Process a single image.
	 *
	 * @param int    $post_id Attachment ID.
	 * @param string $source  Source of generation ('manual' or 'cron').
	 * @return array|WP_Error Result data.
	 */
	public function process_image( $post_id, $source = 'manual' ) {
		$options = get_option( $this->option_name );
		$api_key = isset( $options['api_key'] ) ? $options['api_key'] : '';
		$model   = isset( $options['model'] ) && ! empty( $options['model'] ) ? $options['model'] : 'google/gemini-2.5-flash-lite';

		$enable_alt   = isset( $options['enable_alt'] ) ? (bool) $options['enable_alt'] : true;
		$enable_title = isset( $options['enable_title'] ) ? (bool) $options['enable_title'] : true;

		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_key', __( 'API Key missing', 'kookoo-ai-alt-text-creator' ) );
		}

		// Get Image URL.
		$image_url = wp_get_attachment_url( $post_id );

		// Fallback: Try getting it via image_src if direct url fails.
		if ( ! $image_url ) {
			$src = wp_get_attachment_image_src( $post_id, 'full' );
			if ( $src && isset( $src[0] ) ) {
				$image_url = $src[0];
			}
		}

		if ( ! $image_url ) {
			return new WP_Error( 'missing_url', __( 'Image URL not found', 'kookoo-ai-alt-text-creator' ) );
		}

		// FIX: Ensure URL is properly encoded (e.g., spaces to %20) for the API.
		// OpenRouter/OpenAI will reject URLs with spaces.
		$image_url = str_replace( ' ', '%20', $image_url );

		// FIX: Ensure protocol is present (handle protocol-relative URLs like //example.com/img.jpg).
		if ( strpos( $image_url, '//' ) === 0 ) {
			$image_url = 'https:' . $image_url;
		}

		// Check Supported MIME Type.
		// We use the helper in Settings class if available, else standard image check.
		if ( class_exists( 'Aialtg_Settings' ) && method_exists( 'Aialtg_Settings', 'get_allowed_mimes' ) ) {
			$allowed_mimes = Aialtg_Settings::get_allowed_mimes();
			$current_mime  = get_post_mime_type( $post_id );

			// If allowed_mimes is just 'image', it allows all. If it's an array, we check.
			if ( is_array( $allowed_mimes ) && ! in_array( $current_mime, $allowed_mimes, true ) ) {
				/* translators: %s: MIME type (e.g. image/jpeg) */
				return new WP_Error( 'unsupported_format', sprintf( __( 'Format %s not supported in settings', 'kookoo-ai-alt-text-creator' ), $current_mime ) );
			}
		}

		// Prepare Prompts.
		$default_alt_prompt = 'Generate a concise, descriptive alt text for this image. Do not use phrases like "Image of".';
		$alt_prompt         = isset( $options['prompt'] ) && ! empty( $options['prompt'] ) ? $options['prompt'] : $default_alt_prompt;

		$default_title_prompt = 'Generate a short, descriptive title for this image.';
		$title_prompt         = isset( $options['title_prompt'] ) && ! empty( $options['title_prompt'] ) ? $options['title_prompt'] : $default_title_prompt;

		// Get Global Context.
		$global_context = isset( $options['global_context'] ) && ! empty( $options['global_context'] ) ? $options['global_context'] : '';

		// Context Replacement.
		$parent_title   = 'N/A';
		$parent_content = 'N/A';
		$attachment     = get_post( $post_id );

		if ( $attachment && $attachment->post_parent ) {
			$parent_post = get_post( $attachment->post_parent );
			if ( $parent_post ) {
				if ( ! empty( $parent_post->post_title ) ) {
					$parent_title = $parent_post->post_title;
				}
				if ( ! empty( $parent_post->post_content ) ) {
					// Use wp_strip_all_tags to clean content for the AI prompt.
					$parent_content = wp_strip_all_tags( $parent_post->post_content );
				}
			}
		}

		$replacements = array( $parent_title, $parent_content );
		$search_tags  = array( '{post_title}', '{post_content}' );

		// Apply replacements to all prompt fields.
		$global_context = str_replace( $search_tags, $replacements, $global_context );
		$alt_prompt     = str_replace( $search_tags, $replacements, $alt_prompt );
		$title_prompt   = str_replace( $search_tags, $replacements, $title_prompt );

		// Build System Instruction.
		$system_instructions = "Analyze the image and generate text based on the following instructions.\n";

		// Append Global Context if it exists.
		if ( ! empty( $global_context ) ) {
			$system_instructions .= "CONTEXT:\n" . $global_context . "\n\n";
		}

		$expected_keys = array();
		if ( $enable_alt ) {
			$system_instructions .= '1. Alt Text Prompt: ' . $alt_prompt . "\n";
			$expected_keys[]      = "'alt_text'";
		}
		if ( $enable_title ) {
			$system_instructions .= '2. Title Prompt: ' . $title_prompt . "\n";
			$expected_keys[]      = "'title'";
		}
		$system_instructions .= 'Return ONLY valid JSON with keys ' . implode( ' and ', $expected_keys ) . '.';

		// API Call.
		$response = wp_remote_post(
			'https://openrouter.ai/api/v1/chat/completions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
					'HTTP-Referer'  => add_query_arg( 'page', 'kookoo-ai-alt-text-creator', get_site_url() ),
					'X-Title'       => get_bloginfo( 'name' ),
				),
				'body'    => wp_json_encode(
					array(
						'model'           => $model,
						'response_format' => array( 'type' => 'json_object' ),
						'messages'        => array(
							array(
								'role'    => 'user',
								'content' => array(
									array(
										'type' => 'text',
										'text' => $system_instructions,
									),
									array(
										'type'      => 'image_url',
										'image_url' => array( 'url' => $image_url ),
									),
								),
							),
						),
					)
				),
				'timeout' => 45,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			$err_msg = '';
			if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
				$err_msg = $data['error']['message'];
			} else {
				$err_msg = wp_remote_retrieve_response_message( $response );
			}
			/* translators: 1: HTTP response status code, 2: error message */
			return new WP_Error( 'api_http_error', sprintf( __( 'API returned HTTP %1$d: %2$s', 'kookoo-ai-alt-text-creator' ), $response_code, $err_msg ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_json', __( 'Invalid response format from API', 'kookoo-ai-alt-text-creator' ) );
		}

		if ( isset( $data['error'] ) ) {
			return new WP_Error( 'api_error', isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown error', 'kookoo-ai-alt-text-creator' ) );
		}

		if ( empty( $data['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'empty_response', __( 'Empty response from API', 'kookoo-ai-alt-text-creator' ) );
		}

		// Parse Content.
		$content_str = trim( $data['choices'][0]['message']['content'] );
		$content_str = str_replace( array( '```json', '```' ), '', $content_str );
		$result_json = json_decode( $content_str, true );

		$alt_text = '';
		$title    = '';

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $result_json ) ) {
			// Fallback.
			if ( $enable_alt ) {
				$alt_text = wp_strip_all_tags( $content_str );
			} elseif ( $enable_title ) {
				$title = wp_strip_all_tags( $content_str );
			}
		} else {
			if ( $enable_alt ) {
				$alt_text = isset( $result_json['alt_text'] ) ? wp_strip_all_tags( $result_json['alt_text'] ) : '';
			}
			if ( $enable_title ) {
				$title = isset( $result_json['title'] ) ? wp_strip_all_tags( $result_json['title'] ) : '';
			}
		}

		// Save Data.
		if ( $enable_alt && ! empty( $alt_text ) ) {
			update_post_meta( $post_id, '_wp_attachment_image_alt', $alt_text );
		}
		if ( $enable_title && ! empty( $title ) ) {
			wp_update_post( array(
				'ID'         => $post_id,
				'post_title' => $title,
			) );
		}

		// Mark as processed by the plugin.
		update_post_meta( $post_id, '_aialtg_processed', '1' );

		// Save Generation Meta if enabled.
		if ( ! empty( $options['save_gen_meta'] ) ) {
			// Use gmdate() instead of date() to avoid timezone issues.
			update_post_meta( $post_id, '_aialtg_gen_date', gmdate( 'Y-m-d H:i:s' ) );
			update_post_meta( $post_id, '_aialtg_gen_source', sanitize_text_field( $source ) );
		}

		// Cleanup any previous error log since it succeeded this time.
		delete_post_meta( $post_id, '_aialtg_error_log' );

		return array(
			'alt_text' => $alt_text,
			'title'    => $title,
		);
	}
}