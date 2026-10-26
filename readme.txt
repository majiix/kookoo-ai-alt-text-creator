=== KooKoo AI Alt Text Creator ===

Contributors: micromax2
Tags: images, alt text, seo, accessibility, media library
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 2.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generates descriptive Alt Text and Titles for your Media Library images using AI via OpenRouter. Completely free.

== Description ==

KooKoo AI Alt Text Creator is a complete, 100% free solution for automating image SEO and accessibility on your WordPress site. It uses Artificial Intelligence to analyze your media library images and automatically generate descriptive, context-aware Alt Text and Titles.

By connecting to OpenRouter, this plugin gives you complete autonomy. Connect directly with your personal API key and utilize top-tier vision models (including Gemini 2.5 Flash, GPT-4o, Claude 3.5, and free community models) at zero platform markup.

**Key Features (All 100% Free)**

* **OpenRouter AI Gateway:** Connect via OpenRouter using your personal API key with access to hundreds of vision-capable models.
* **Alt Text & Title Generation:** Automatically generate descriptive Alt Texts and Titles with customizable AI prompt instructions.
* **One-Click Manual Generation:** Instantly generate or regenerate metadata for individual images directly from the Media Library list view.
* **Automated Background Processing:** Process your library images automatically using the built-in Cron scheduler with customizable batch sizes and intervals.
* **Context-Aware Generation:** The AI reads the Title and Content of the post or page the image is attached to for tailored relevancy.
* **Global Context:** Add universal instructions (e.g., "Always emphasize architectural elements") applied across every request.
* **Generation Metadata:** Track and view AI generation timestamps and execution sources directly in the attachment edit screen.
* **Smart Error Handling & Tools:** Retry failed images with one click, or scan and repair accidental raw JSON code with the JSON Fixer tool.
* **Localhost & Private Network Support:** Automatically detects local development environments and transmits images as Base64 data for seamless local testing.
* **Format Control:** Choose exactly which file types to process (JPG, PNG, WEBP, etc.).

== External Services ==

This plugin relies on external AI API services to provide image analysis and text generation capabilities. Data is transmitted to the following third-party service:

### OpenRouter
> Service URL: https://openrouter.ai/
>
> Data Sent: When using OpenRouter, image attachment URLs (or Base64-encoded image data for local or private environments), post titles, post content (context), the site URL (HTTP-Referer header), and the site title (X-Title header) are sent to the OpenRouter API (`https://openrouter.ai/api/v1/chat/completions`) when generating metadata or testing the connection. Additionally, the settings dashboard queries `https://openrouter.ai/api/v1/models` to retrieve available vision models.
>
> Terms of Service: https://openrouter.ai/terms
>
> Privacy Policy: https://openrouter.ai/privacy

**Note:** You must supply your own OpenRouter API key. No personal user information is transmitted or stored externally.

== Installation ==

1. Upload the `kookoo-ai-alt-text-creator` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to Settings > AI Alt Text to configure your API gateway and options.

== Configuration ==

### 1. General (API Configuration)
* **API Gateway:** Configured for OpenRouter with flexible custom model support.
* **API Key:** Provide your personal OpenRouter API key.
* **Model Selection:** Select a vision-capable AI model from the dynamic dropdown, or enter any custom model ID.
* **Test Connection:** Verify your API credentials and model configuration with a single click.

### 2. Generation Options
* **Global Context:** Universal instructions appended to every request. Supports `{post_title}` and `{post_content}` tags.
* **Supported Image Formats:** Define allowed image file extensions (e.g. `jpg,jpeg,png,webp`).
* **Metadata Toggles & Prompts:** Independently enable and customize prompt instructions for Alt Text and Titles.

### 3. Bulk Generation (Cron)
* **Enable Background Processing:** Turns on automated background batch generation.
* **Batch Size:** Number of images processed per run (recommended: 1-5 to prevent server timeouts).
* **Interval:** Frequency of background runs (in minutes).

== Frequently Asked Questions ==

= Is this plugin completely free? =

Yes! Alt Text and Title generation via OpenRouter, single-item generation in the Media Library, and automated Cron background processing are 100% free.

= What AI models can I use? =

You can use any vision-capable model supported by OpenRouter. Select from hundreds of models (including Gemini 2.5 Flash, GPT-4o, Claude 3.5, and free community models) or enter any custom model ID.

= Does this plugin modify my actual image files? =

No. It only updates metadata fields (Alt Text and Title) in the WordPress database. Your original image files remain untouched.

= What happens if I uninstall the plugin? =

Plugin transients and scheduled background tasks are cleanly cleared upon uninstall. Your settings and image generation metadata remain safely stored in the database if you ever reinstall the plugin. All generated Alt Texts and Titles remain permanently stored in WordPress as core media metadata.

== Changelog ==

= 2.5.0 =
* Completely standalone free release focused on Alt Text and Title generation via OpenRouter.
* Removed multi-gateway switcher, direct provider APIs, and unused scripts for a clean free release.
* Cleaned up admin assets, stylesheet footprint, and settings panel.

= 2.4.1 =
* Security: Validate image file paths against the WordPress uploads directory prior to Base64 encoding to prevent path traversal.
* Security: Mask internal error and exception details in AJAX responses.
