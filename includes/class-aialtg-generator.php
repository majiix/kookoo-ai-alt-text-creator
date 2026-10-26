<?php
/**
 * Generator Class for AI Alt Text Creator
 * Handles API calls and data processing.
 *
 * @package AI_Alt_Text_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Aialtg_Generator' ) ) {
/**
 * Handles generating alt texts and titles via AI APIs.
 */
class Aialtg_Generator {

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	private $option_name = 'aialtg_settings';

	/**
	 * Process a single image.
	 *
	 * @param int    $post_id Attachment ID.
	 * @param string $source  Source of generation ('manual', 'cron').
	 * @return array|WP_Error Result data.
	 */
	public function process_image( $post_id, $source = 'manual' ) {
		$options = get_option( $this->option_name );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$api_key = isset( $options['api_key'] ) ? trim( $options['api_key'] ) : '';

		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_key', __( 'API Key missing', 'kookoo-ai-alt-text-creator' ) );
		}

		$model = isset( $options['model'] ) && ! empty( $options['model'] ) ? $options['model'] : 'google/gemini-2.5-flash-lite';

		$image_url_or_error = $this->prepare_image_url( $post_id );
		if ( is_wp_error( $image_url_or_error ) ) {
			return $image_url_or_error;
		}
		$image_url = $image_url_or_error;

		$prompts_to_add = $this->build_prompts_payload( $post_id, $options );

		if ( empty( $prompts_to_add ) ) {
			update_post_meta( $post_id, '_aialtg_processed', '1' );
			delete_post_meta( $post_id, '_aialtg_error_log' );
			return array(
				'alt_text' => get_post_meta( $post_id, '_wp_attachment_image_alt', true ),
				'title'    => get_the_title( $post_id ),
			);
		}

		$system_instructions = $this->build_system_instructions( $prompts_to_add, $options, $post_id );

		$api_url = 'https://openrouter.ai/api/v1/chat/completions';
		$headers = array(
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'application/json',
			'HTTP-Referer'  => add_query_arg( 'page', 'kookoo-ai-alt-text-creator', get_site_url() ),
			'X-Title'       => wp_strip_all_tags( get_bloginfo( 'name' ) ),
		);

		$response = wp_remote_post(
			$api_url,
			array(
				'headers' => $headers,
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

		$content_str = trim( $data['choices'][0]['message']['content'] );
		$result_json = $this->robust_json_decode( $content_str );

		if ( false === $result_json ) {
			return new WP_Error( 'json_parse_failed', __( 'API response was JSON but could not be parsed', 'kookoo-ai-alt-text-creator' ) );
		}

		return $this->save_generated_metadata( $post_id, $result_json, $prompts_to_add, $source );
	}

	/**
	 * Prepare image URL or Base64 data URI.
	 *
	 * @param int $post_id Attachment ID.
	 * @return string|WP_Error URL or WP_Error.
	 */
	private function prepare_image_url( $post_id ) {
		$image_url = wp_get_attachment_url( $post_id );
		if ( ! $image_url ) {
			return new WP_Error( 'missing_url', __( 'Image URL not found', 'kookoo-ai-alt-text-creator' ) );
		}

		$image_url = str_replace( ' ', '%20', $image_url );
		if ( 0 === strpos( $image_url, '//' ) ) {
			$image_url = 'https:' . $image_url;
		}

		$allowed_mimes = Aialtg_Settings::get_allowed_mimes();
		$current_mime  = get_post_mime_type( $post_id );

		if ( is_array( $allowed_mimes ) && ! in_array( $current_mime, $allowed_mimes, true ) ) {
			/* translators: %s: MIME type */
			return new WP_Error( 'unsupported_format', sprintf( __( 'Format %s not supported in settings', 'kookoo-ai-alt-text-creator' ), $current_mime ) );
		}

		$file_path = get_attached_file( $post_id );
		if ( $file_path && file_exists( $file_path ) && is_readable( $file_path ) ) {
			$uploads   = wp_upload_dir();
			$basedir   = wp_normalize_path( $uploads['basedir'] );
			$real_path = wp_normalize_path( (string) realpath( $file_path ) );

			if ( 0 === strpos( $real_path, $basedir ) ) {
				$file_size = filesize( $real_path );
				if ( $file_size > 0 && $file_size <= 5 * 1024 * 1024 ) {
					$host = wp_parse_url( $image_url, PHP_URL_HOST );
					$is_local = ( 'localhost' === $host || '127.0.0.1' === $host || preg_match( '/^(127\.|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', (string) $host ) );
					if ( $is_local || ! wp_http_validate_url( $image_url ) ) {
						$file_data = file_get_contents( $real_path );
						if ( $file_data ) {
							$mime_type   = get_post_mime_type( $post_id );
							$base64_data = base64_encode( $file_data );
							$image_url   = 'data:' . $mime_type . ';base64,' . $base64_data;
						}
					}
				}
			}
		}

		return $image_url;
	}

	/**
	 * Build prompts array based on options for alt text and title.
	 *
	 * @param int   $post_id Attachment ID.
	 * @param array $options Settings array.
	 * @return array Keyed prompt instructions.
	 */
	private function build_prompts_payload( $post_id, $options ) {
		$enable_alt   = isset( $options['enable_alt'] ) ? (bool) $options['enable_alt'] : false;
		$enable_title = isset( $options['enable_title'] ) ? (bool) $options['enable_title'] : false;

		// Context replacements.
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
					$parent_content = wp_strip_all_tags( $parent_post->post_content );
				}
			}
		}

		$search_tags  = array( '{post_title}', '{post_content}' );
		$replacements = array( $parent_title, $parent_content );

		$prompts = array();

		if ( $enable_alt ) {
			$default_alt = 'Generate a concise, descriptive alt text for this image. Do not use phrases like "Image of".';
			$alt_prompt  = ! empty( $options['prompt'] ) ? $options['prompt'] : $default_alt;
			$alt_prompt  = str_replace( $search_tags, $replacements, (string) $alt_prompt );
			$prompts['alt_text'] = $alt_prompt;
		}

		if ( $enable_title ) {
			$default_title = 'Generate a short, descriptive title for this image.';
			$title_prompt  = ! empty( $options['title_prompt'] ) ? $options['title_prompt'] : $default_title;
			$title_prompt  = str_replace( $search_tags, $replacements, (string) $title_prompt );
			$prompts['title'] = $title_prompt;
		}

		return $prompts;
	}

	/**
	 * Build system instructions with context and numbered prompt requirements.
	 *
	 * @param array $prompts Prompts to include.
	 * @param array $options Settings array.
	 * @param int   $post_id Attachment ID.
	 * @return string Instructions string.
	 */
	private function build_system_instructions( $prompts, $options, $post_id ) {
		$system_instructions = "Analyze the image and generate text based on the following instructions.\n";

		$default_context = 'Focus on the visual contents of the image. Refer to the associated page title ({post_title}) and content ({post_content}) to provide relevant context.';
		$global_context  = isset( $options['global_context'] ) && ! empty( $options['global_context'] ) ? $options['global_context'] : $default_context;

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
					$parent_content = wp_strip_all_tags( $parent_post->post_content );
				}
			}
		}

		$global_context = str_replace(
			array( '{post_title}', '{post_content}' ),
			array( $parent_title, $parent_content ),
			(string) $global_context
		);

		if ( ! empty( $global_context ) ) {
			$system_instructions .= "CONTEXT:\n" . $global_context . "\n\n";
		}

		$expected_keys = array();
		$index = 1;
		foreach ( $prompts as $key => $prompt ) {
			$label = ucfirst( str_replace( '_', ' ', $key ) );
			$system_instructions .= "{$index}. {$label} Prompt: {$prompt}\n";
			$expected_keys[]      = "'{$key}'";
			$index++;
		}
		$system_instructions .= 'Return ONLY valid JSON with keys ' . implode( ' and ', $expected_keys ) . '.';

		return $system_instructions;
	}

	/**
	 * Save generated metadata to attachment.
	 *
	 * @param int    $post_id Attachment ID.
	 * @param array  $result_json Decoded AI response JSON.
	 * @param array  $prompts_to_add Active prompts payload.
	 * @param string $source Generation source.
	 * @return array Saved data result.
	 */
	private function save_generated_metadata( $post_id, $result_json, $prompts_to_add, $source ) {
		$update_args  = array( 'ID' => $post_id );
		$needs_update = false;

		$alt_text = get_post_meta( $post_id, '_wp_attachment_image_alt', true );
		if ( isset( $prompts_to_add['alt_text'] ) && isset( $result_json['alt_text'] ) ) {
			$alt_text = wp_strip_all_tags( $result_json['alt_text'] );
			update_post_meta( $post_id, '_wp_attachment_image_alt', $alt_text );
		}

		$title = get_the_title( $post_id );
		if ( isset( $prompts_to_add['title'] ) && isset( $result_json['title'] ) ) {
			$title                     = wp_strip_all_tags( $result_json['title'] );
			$update_args['post_title'] = $title;
			$needs_update              = true;
		}

		if ( $needs_update ) {
			wp_update_post( $update_args );
		}

		// Mark processed and clear error log.
		update_post_meta( $post_id, '_aialtg_processed', '1' );
		delete_post_meta( $post_id, '_aialtg_error_log' );

		// Save generation metadata.
		update_post_meta( $post_id, '_aialtg_gen_date', gmdate( 'Y-m-d H:i:s' ) );
		update_post_meta( $post_id, '_aialtg_gen_source', sanitize_text_field( $source ) );

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

		if ( preg_match( '/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $content, $matches ) ) {
			$content = trim( $matches[1] );
		}

		$decoded = json_decode( $content, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
			return $decoded;
		}

		if ( preg_match( '/(\{[\s\S]*\})/i', $content, $matches ) ) {
			$potential_json = trim( $matches[1] );
			$decoded        = json_decode( $potential_json, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				return $decoded;
			}

			$cleaned_json = preg_replace( '/,\s*([\}\]])/', '$1', $potential_json );
			$decoded      = json_decode( $cleaned_json, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return false;
	}
}
}
