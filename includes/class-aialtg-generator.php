<?php
/**
 * Generator Class for AI Alt Text Creator
 * Handles API calls and data processing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Aialtg_Generator' ) ) {
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
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		// Allow filtering to skip image generation.
		$skip = apply_filters( 'aialtg_skip_image_generation', false, $post_id, $source );
		if ( $skip ) {
			// Mark as processed so it doesn't get retried or stuck in the queue.
			update_post_meta( $post_id, '_aialtg_processed', '1' );

			// Clean up any previous error logs since we're skipping it.
			delete_post_meta( $post_id, '_aialtg_error_log' );

			return array(
				'alt_text' => get_post_meta( $post_id, '_wp_attachment_image_alt', true ),
				'title'    => get_the_title( $post_id ),
				'skipped'  => true,
			);
		}

		$api_key = isset( $options['api_key'] ) ? $options['api_key'] : '';
		$model   = isset( $options['model'] ) && ! empty( $options['model'] ) ? $options['model'] : 'google/gemini-2.5-flash-lite';

		$enable_alt   = isset( $options['enable_alt'] ) ? (bool) $options['enable_alt'] : true;
		$enable_title = isset( $options['enable_title'] ) ? (bool) $options['enable_title'] : true;

		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_key', __( 'API Key missing', 'kookoo-ai-alt-text-creator' ) );
		}

		// Get Image URL.
		$image_url = wp_get_attachment_url( $post_id );

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
		$allowed_mimes = Aialtg_Settings::get_allowed_mimes();
		$current_mime  = get_post_mime_type( $post_id );

		// If allowed_mimes is just 'image', it allows all. If it's an array, we check.
		if ( is_array( $allowed_mimes ) && ! in_array( $current_mime, $allowed_mimes, true ) ) {
			/* translators: %s: MIME type (e.g. image/jpeg) */
			return new WP_Error( 'unsupported_format', sprintf( __( 'Format %s not supported in settings', 'kookoo-ai-alt-text-creator' ), $current_mime ) );
		}

		// Check if URL is local/private, and if so, load it as Base64 to prevent API fetch issues.
		$file_path = get_attached_file( $post_id );
		if ( $file_path && file_exists( $file_path ) && is_readable( $file_path ) ) {
			$file_size = filesize( $file_path );
			// 5 MB limit for Base64 encoding.
			if ( $file_size > 0 && $file_size <= 5 * 1024 * 1024 ) {
				$host = wp_parse_url( $image_url, PHP_URL_HOST );
				$is_local = ( 'localhost' === $host || '127.0.0.1' === $host || preg_match( '/^(127\.|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', (string) $host ) );
				if ( $is_local || ! wp_http_validate_url( $image_url ) ) {
					$file_data = file_get_contents( $file_path );
					if ( $file_data ) {
						$mime_type = get_post_mime_type( $post_id );
						$base64_data = base64_encode( $file_data );
						$image_url = 'data:' . $mime_type . ';base64,' . $base64_data;
					}
				}
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
		$global_context = str_replace( $search_tags, $replacements, (string) $global_context );
		$alt_prompt     = str_replace( $search_tags, $replacements, (string) $alt_prompt );
		$title_prompt   = str_replace( $search_tags, $replacements, (string) $title_prompt );

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
					'X-Title'       => wp_strip_all_tags( get_bloginfo( 'name' ) ),
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
		$result_json = $this->robust_json_decode( $content_str );

		$alt_text = '';
		$title    = '';

		if ( false === $result_json ) {
			// It's not valid JSON. Check if it's likely a JSON attempt.
			$is_likely_json = ( strpos( $content_str, '{' ) === 0 || strpos( $content_str, '"alt_text"' ) !== false || strpos( $content_str, '"title"' ) !== false );
			
			if ( ! $is_likely_json ) {
				// Fallback to raw string if it doesn't look like JSON.
				if ( $enable_alt ) {
					$alt_text = wp_strip_all_tags( $content_str );
				} elseif ( $enable_title ) {
					$title = wp_strip_all_tags( $content_str );
				}
			} else {
				return new WP_Error( 'json_parse_failed', __( 'API response was JSON but could not be parsed', 'kookoo-ai-alt-text-creator' ) );
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

		// Save Generation Meta if enabled and allowed.
		$allow_save = apply_filters( 'aialtg_allow_save_gen_meta', false );
		if ( ! empty( $options['save_gen_meta'] ) && $allow_save ) {
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



	/**
	 * Extracts and parses JSON content from the API response robustly.
	 *
	 * @param string $content Raw content from API.
	 * @return array|false Parsed array or false on failure.
	 */
	private function robust_json_decode( $content ) {
		$content = trim( $content );
		
		// Remove markdown code block wrappers.
		if ( preg_match( '/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $content, $matches ) ) {
			$content = trim( $matches[1] );
		}
		
		// Try parsing directly.
		$decoded = json_decode( $content, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
			return $decoded;
		}
		
		// If direct parsing failed, try extracting first '{' to last '}' via regex.
		if ( preg_match( '/(\{[\s\S]*\})/i', $content, $matches ) ) {
			$potential_json = trim( $matches[1] );
			$decoded = json_decode( $potential_json, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				return $decoded;
			}
			
			// Try to clean up common JSON issues: trailing commas before closing braces/brackets.
			$cleaned_json = preg_replace( '/,\s*([\}\]])/', '$1', $potential_json );
			$decoded = json_decode( $cleaned_json, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				return $decoded;
			}
		}
		
		return false;
	}
}
}