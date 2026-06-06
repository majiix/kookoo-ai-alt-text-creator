

=== KooKoo AI Alt Text Creator ===

Contributors: micromax2

Tags: images, alt text, seo, accessibility, media library

Requires at least: 6.0

Tested up to: 7.0

Stable tag: 1.7.0

License: GPLv2 or later

License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically generates descriptive Alt Text and Titles for your Media Library images using AI via OpenRouter.

== Description ==

AI Alt Text Creator is a complete solution for automating image SEO and accessibility on your WordPress site. It uses Artificial Intelligence to analyze your images and generate descriptive, context-aware Alt Text and Titles.

By connecting to OpenRouter, this plugin bypasses expensive monthly subscriptions, giving you direct access to the world's best AI models (including Google Gemini, GPT-4, and Claude 3.5) at the lowest possible cost.

**🚀 Key Features**

**One-Click Manual Generation:** Instantly generate metadata for individual images directly from the Media Library list view. Perfect for new uploads or refining specific images.

**Automated Background Processing:** Process your entire back catalog automatically using the built-in Cron scheduler. Configure batch sizes and intervals to suit your server's capacity.

**Context-Aware Descriptions:** The AI reads the Title and Content of the post/page the image is attached to. This ensures the generated Alt Text is relevant to your specific article, not just a generic description of the visual.

**Global Context:** Add custom instructions (e.g., "Always mention our brand name 'Acme Corp'") that apply to every image generated.

**Smart Error Handling:**

**Retry Failed:** Automatically logs failed attempts. You can retry all failed images with one click from the settings page.

**JSON Fixer:** A specialized tool to scan and fix images where the AI might have accidentally saved raw code instead of text.

**Detailed Logging:** View the exact generation timestamp, source (Manual vs Cron), and any error messages directly in the "Edit Media" screen.

**Cost Control:** You bring your own API key. You pay only for exactly what you use, with no markup.

**Format Control:** Choose exactly which file types to process (JPG, PNG, WEBP, etc.).

**🧠 Why OpenRouter?**

OpenRouter is an AI aggregator that offers significant benefits for WordPress users:

**Cheaper:** Models like Google Gemini 1.5 Flash are incredibly affordable (often fractions of a cent per image).

**Flexible:** Switch models instantly. If a new model is released tomorrow, you can simply paste its ID and use it immediately.

**No Middleman:** You use your own API key.

**💡 Context & Prompts**

You can customize exactly how the AI behaves using placeholders in your prompts:

**Examples of Global Context:**

    Inputs:
    – Page Topic: [{post_title}]
    – Page Content: [{post_content}]
    – Image: [attached image]

Examples of Alt Text Prompt:

    Role: Web Accessibility and SEO Expert.
    Task: Generate a single, optimized alt text string (MAX 125 characters) for the provided image, situated within the context of inputs.

Examples of Alt Text Prompt:

    Role: SEO Copywriting Specialist.
    Task: Generate 1 optimized image title based on the provided topic and attached image.

== External Services ==

This plugin relies on OpenRouter as a third-party service to provide Artificial Intelligence capabilities.

> Service Name: OpenRouter
>
> Service URL: https://openrouter.ai/
>
> Data Sent: Image URLs, Post Titles, and Post Content (context) are
> sent to the OpenRouter API for processing.
>
> Terms of Service: OpenRouter Terms https://openrouter.ai/terms
>
> Privacy Policy: OpenRouter Privacy Policy
> https://openrouter.ai/privacy

**Note:** You must obtain your own API key from OpenRouter to use this plugin.

== Installation ==

Upload the plugin folder to the /wp-content/plugins/ directory.

Activate the plugin through the 'Plugins' menu in WordPress.

Navigate to Settings > AI Alt Text to configure the plugin.

== Configuration ==

1-API Configuration

**OpenRouter API Key:** Sign up at OpenRouter.ai and paste your key here.

**Model Selection:** Choose the model you wish to use from the dynamic dropdown menu (default: google/gemini-2.5-flash-lite).

2-Generation Options

**Global Context:** Instructions sent with every request. Great for setting the "persona" of the AI.

**Supported Image Formats:** Define which file extensions the plugin should process.

**Enable Alt Text / Title:** Toggle which metadata fields you want to generate.

**Prompts:** Customize the specific instructions for generating Alt Text vs Titles.

**Save Generation Info:** Enables logging of timestamps and error messages to the database.

3-Bulk Generation (Cron)

**Enable Background Processing:** Turns on the automatic generator.

**Batch Size:** How many images to process per run. Keep this low (1-2) to prevent server timeouts.

**Interval:** How often the job runs (in minutes).

== Frequently Asked Questions ==

= What AI models are supported by OpenRouter? =

OpenRouter supports a vast array of models from all major providers. As of the latest update, supported model families include (but are not limited to):

    Google: Gemini, PaLM
    
    OpenAI: GPT (GPT-3.5, GPT-4, GPT-4o, etc.)
    
    Anthropic: Claude (Haiku, Sonnet, Opus)
    
    Meta: Llama
    
    Mistral AI: Mistral, Mixtral, Codestral
    
    DeepSeek: DeepSeek Chat/Coder
    
    Qwen: Qwen (Alibaba)
    
    Microsoft: WizardLM, Phi
    
    Perplexity: Perplexity Online/Chat
    
    X.ai: Grok
    
    Cohere: Command
    
    Nvidia: Nemotron
    
    Amazon: Nova, Bedrock
    
    Databricks: DBRX
    
    Nous Research: Hermes
    
    Liquid: Liquid
    
    Arcee AI: Arcee
    
    Moonshot AI: Kimi
    
    Z.ai: GLM
    
    MiniMax: MiniMax

You can find the specific Model IDs for these families on the OpenRouter Models page.

= Does this plugin modify my actual image files? =

No. It only updates the metadata (Alt Text and Title) in the WordPress database. Your physical image files remain untouched.

= Will it overwrite my existing Alt Text? =

Bulk Generation (Background): No. The background process automatically skips any image that has already been successfully processed by the plugin to save you money and preserve your data.

Manual Generation (Button): Yes. If you click the "Regenerate" button on a specific image in the Media Library, it will overwrite the existing text with the new AI result.

= What happens if I uninstall the plugin? =

If you delete the plugin via the Plugins screen:

It will clean up its own settings and temporary statistics.

It will remove internal logs (timestamps, error logs).

Crucially: The Alt Text and Titles generated for your images will remain. They become part of your site's standard content and are not deleted.

= My bulk generation seems stuck. What do I do? =

Go to Settings > AI Alt Text. Look at the "Statistics" box.

Check the "Failed" count. If images are failing, check the "Edit Media" screen of a failed image to see the error message.

Use the "Retry Failed Images" button to move them back to the pending queue.

Use the "Reset Cron Progress" button if you want to completely restart the analysis from scratch (this allows the plugin to look at all images again).

== Changelog ==

= 1.7.0 =

* Added a dynamic model selector dropdown that automatically retrieves all available models from OpenRouter via AJAX with a skeleton loading screen.
* Grouped models in the dropdown by provider and added a visual camera indicator (📷) next to models that support image/vision analysis.
* Implemented a seamless fallback text input for entering custom model IDs or when the API fetching is unavailable.
* Updated the default model to google/gemini-2.5-flash-lite.
* Added robust HTTP response validation for API requests.
* Resolved a bug in the generation process where successful generation did not clear previous metadata error logs for the image.
* Improved response JSON structure safety validation.
* Removed redundant logic and ensured optimal codebase structure.
* Bumped compatibility tag to support WordPress 7.0.

= 1.6.0 =

Redesigned Admin UI
Added Cron

= 1.5.3 =

Improved Cron reliability with a new watchdog function.

Added error logging for failed API requests in the Edit Media screen.

Fixed issue where failed images would block the bulk generation queue.

Added uninstall.php for clean removal of plugin data.

Added "Scan & Fix JSON Errors" tool to settings.

Added "Retry Failed Images" tool to settings.

= 1.0.0 =

Initial release.