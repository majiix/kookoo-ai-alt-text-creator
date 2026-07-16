=== KooKoo AI Alt Text Creator ===

Contributors: micromax2
Tags: images, alt text, seo, accessibility, media library
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.9.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically generates descriptive Alt Text and Titles for your Media Library images using AI via OpenRouter, OpenAI, or Google Gemini.

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

== Pro Version ==

Unlock the ultimate media optimization toolkit with the **KooKoo AI Alt Text Creator Pro Addon**. 

### How to Get the Pro Version
You can purchase the Pro Addon directly from [Violo.ir](https://violo.ir/?p=14). Once purchased, upload and activate the Pro Addon plugin alongside the free version of KooKoo AI Alt Text Creator.

### What You Get with Pro
* **Auto-Generate on Upload:** Automatically analyzes and generates image details (Alt, Title, Caption, Description) the moment you upload them to the Media Library.
* **Media Library Bulk Actions:** Select multiple images and generate details in bulk directly from the WordPress Media Library list view.
* **Generate Captions & Descriptions:** Expand image metadata beyond alt text and titles. Automate descriptive captions and detailed content descriptions with custom prompts.
* **Direct API Gateways (OpenAI & Gemini):** Connect directly to OpenAI (ChatGPT) and Google Gemini direct APIs using your own keys to bypass third-party platforms.
* **Granular Overwrite Protection:** Enable "Do Not Overwrite Existing Details" to selectively generate and save only empty metadata fields. If all enabled fields are already filled, the image is skipped without making API calls.
* **Unlimited Cron Scheduler:** Remove all background processing limits. Unlocks intervals down to 1 minute and unlimited batch sizes.
* **WP-CLI Integration:** Trigger metadata generation directly from the server command line.
* **Save Generation Metadata:** Save timestamps and execution source indicators in database.

### Why You Should Upgrade to Pro
* **Save Hours of Manual Work:** Upload and automate everything. Your site will automatically generate search-engine optimized alt texts, titles, captions, and descriptions in the background.
* **Better SEO & Accessibility:** Captions and descriptions add deep, keyword-rich context to your image pages and attachments, boosting search visibility and accessibility compliance.
* **Complete Gateway Autonomy:** Choose direct endpoints to utilize your custom account credits and direct API models like gpt-4o-mini and gemini-2.5-flash.

== External Services ==

This plugin relies on OpenRouter as a third-party service to provide Artificial Intelligence capabilities. When the Pro Addon is active, it can optionally connect directly to OpenAI and Google Gemini APIs.

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

> Service Name: OpenAI Direct API (Optional Pro Addon Feature)
>
> Service URL: https://openai.com/
>
> Data Sent: Image URLs, Post Titles, and Post Content (context) are
> sent to the OpenAI API for processing.
>
> Terms of Service: OpenAI Terms https://openai.com/policies/terms-of-use
>
> Privacy Policy: OpenAI Privacy Policy
> https://openai.com/policies/privacy-policy

> Service Name: Google Gemini Direct API (Optional Pro Addon Feature)
>
> Service URL: https://ai.google.dev/
>
> Data Sent: Image URLs, Post Titles, and Post Content (context) are
> sent to the Google Generative Language API for processing.
>
> Terms of Service: Google APIs Terms of Service https://developers.google.com/terms
>
> Privacy Policy: Google Privacy Policy
> https://policies.google.com/privacy

**Note:** You must obtain your own API keys from OpenRouter, OpenAI, or Google to use these services.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to Settings > AI Alt Text to configure the plugin.

== Configuration ==

### 1. API Configuration
* **API Gateway:** Choose between OpenRouter, OpenAI Direct, and Google Gemini Direct.
* **API Keys:** Provide the API key for your chosen gateway.
* **Model Selection:** Choose the model you wish to use from the dynamic dropdown menu (default: google/gemini-2.5-flash-lite).

### 2. Generation Options
* **Global Context:** Instructions sent with every request. Great for setting the "persona" of the AI.
* **Supported Image Formats:** Define which file extensions the plugin should process.
* **Enable Fields:** Toggle which metadata fields you want to generate (Alt Text, Title, Caption, Description).
* **Prompts:** Customize the specific instructions for generating Alt Text, Titles, Captions, or Descriptions. Includes "Reset to Default" buttons.
* **Save Generation Info:** Enables logging of timestamps and error messages to the database.

### 3. Bulk Generation (Cron)
* **Enable Background Processing:** Turns on the automatic generator.
* **Batch Size:** How many images to process per run. Keep this low (1-2) in free version to prevent server timeouts. Unlimited in Pro.
* **Interval:** How often the job runs (in minutes).

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

= Can I generate Alt Text or Titles for free? =

Yes. Choose a free model from OpenRouter to generate metadata without any cost.

== Changelog ==

= 1.9.0 =
* Added Direct API Gateways for OpenAI and Google Gemini (Pro).
* Added dynamic model switcher and placeholder updates based on selected Gateway.
* Added option to generate image Captions and Descriptions with customizable prompts (Pro).
* Added instant upload hook to automatically generate details on image upload (Pro).
* Added Media Library bulk actions to process multiple images at once (Pro).
* Refactored skip logic to granular field-level overwrite prevention (Pro).
* Added Cron batch size and interval limits for free version, unlocked in Pro.
* Added "Reset to Default" action link buttons under prompt textareas.
* Redesigned settings layout to use premium HSL colors, focus states, and glassmorphism.
* Added AJAX Test API Connection button to instantly verify API key and model config.

= 1.8.3 =
* Added premium option to skip images with existing Alt Texts during background/cron runs.
* Added premium option to save generation metadata (timestamp/source).
* Added premium WP-CLI command integration (`wp kookoo-alt-text process`).
* Implemented Pro addon version compatibility validation checks and warning notices.

= 1.8.2 =
* Removed licensing logic and made all features completely free.
* Discontinued EDD Software Licensing activation/deactivation support.
* Made saving of generation metadata (timestamp/source) fully unlocked for all users.

= 1.8.1 =
* Consolidated duplicated AJAX event handlers in settings screen.
* Removed redundant cron health watchdog hook and method.
* Removed redundant cron interval filtering code.
* Optimized database uninstall metadata deletion query using SQL IN operator.

= 1.0.0 =
* Initial release.