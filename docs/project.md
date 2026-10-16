# KooKoo AI Alt Text Creator - Project Documentation

## Overview
KooKoo AI Alt Text Creator is a WordPress plugin that automatically generates descriptive Alt Texts and Titles for images in the Media Library using AI models aggregated by OpenRouter. It offers both manual, one-click generation and background bulk generation via WordPress Cron scheduler.

## Tech Stack
* **PHP**: 7.4+ (Enforced via `Requires PHP: 7.4` header, compatible with PHP 8.0+)
* **WordPress**: 6.0+ (Tested up to 7.1.0)
* **API**: OpenRouter Chat Completions API (using WordPress HTTP API)
* **Assets**: Vanilla CSS & JavaScript / jQuery (for settings panel animations, skeleton loaders, and AJAX actions)

## Dependencies
* None (uses native WordPress APIs)

## Architecture
* [kookoo-ai-alt-text-creator.php](../kookoo-ai-alt-text-creator.php): Plugin bootstrap, hooks registration, lifecycle event dispatch (`aialtg_loaded`), public API helper (`aialtg_process_image`), batch action hook (`aialtg_process_batch`), textdomain loader (`load_plugin_textdomain`), privacy policy suggester (`wp_add_privacy_policy_content`), and error-masked AJAX handlers controller.
* [includes/class-aialtg-settings.php](class-aialtg-settings.php): Handles settings page registration, single-statement SQL query preparation, sanitization, dashboard interface rendering, extensible Pro settings hooks (`aialtg_register_settings_fields`), fully localized Pro preview tab, and filter-decoupled upsell UI (`aialtg_show_pro_card`, `aialtg_show_pro_tab`, `aialtg_settings_help_sections`).
* [includes/class-aialtg-generator.php](class-aialtg-generator.php): Core logic for generating Alt Texts and Titles, performing remote requests to OpenRouter, uploads-directory validated Base64 conversions, parsing JSON, and filter-based API key resolution (`aialtg_gateway_api_key`).
* [includes/class-aialtg-cron.php](class-aialtg-cron.php): Handles automated background queue processing via WP Cron with configurable batch limits and host timeout advisories.
* [docs/design_system.md](design_system.md): Detailed specification of the plugin's visual tokens, typography, layouts, components, and animations.
* [.gitattributes](../.gitattributes): Packaging rules to exclude development files and documentation (`docs/`, `.codegraph/`) from release archives and SVN exports.
* [kookoo-ai-alt-text-creator-pro-addon/kookoo-ai-alt-text-creator-pro-addon.php](../../kookoo-ai-alt-text-creator-pro-addon/kookoo-ai-alt-text-creator-pro-addon.php): Pro add-on bootstrap, textdomain loader, screen-scoped version warning notices, hooks into `aialtg_loaded`, registers independent Pro settings (`aialtg_pro_settings`), implements Direct API gateways (OpenAI, Gemini), caption and description generation, generation metadata logging, upload hooks, and bulk actions.
* [kookoo-ai-alt-text-creator-pro-addon/includes/class-aialtg-cli.php](../../kookoo-ai-alt-text-creator-pro-addon/includes/class-aialtg-cli.php): Registers premium WP-CLI commands (`wp kookoo-alt-text process`) using public API helper and action hooks.
* [kookoo-ai-alt-text-creator-pro-addon/uninstall.php](../../kookoo-ai-alt-text-creator-pro-addon/uninstall.php): Handles uninstallation cleanup for the Pro add-on, unconditionally removing `aialtg_pro_settings` and Pro generation metadata.

## Assumptions & Pre-Submission Decisions
* **PHP Requirement**: Declared `Requires PHP: 7.4` to support syntax requirements while ensuring wide host compatibility.
* **WordPress Core Target**: Tested up to `7.1` maintained per user direction.
* **Plugin URI**: Maintained as `https://wordpress.org/plugins/kookoo-ai-alt-text-creator/` per user direction.
* **Author & Contributor Handles**: Maintained as `micromax` (Author header) and `micromax2` (Contributors list) per user direction.
* **Release Packaging**: `docs/` is retained for internal architecture reference but excluded from release builds and WordPress.org SVN exports via `.gitattributes`.
* **Internationalization**: The Pro Features showcase tab is fully localized with `esc_html__()` and included in `languages/kookoo-ai-alt-text-creator.pot`. Added `Status: Generated with AI` for free attachment meta box.
* **External Services Disclosure**: Explicitly discloses `https://openrouter.ai/api/v1/models` queried on settings screen alongside `/api/v1/chat/completions`.
* **Dormant Code Purge**: Free `admin.css` purged of unused Pro badge classes, which are now scoped into Pro addon asset enqueue.
* **Error & Status Observability**: Free attachment meta box reports `Status: Generated with AI` on successful processing; manual AJAX generation failures persist error logs and mark status as failed.

## Current Features
* **AI Generation**: Descriptive Alt Text and Title generation with custom vision/image models.
* **Manual Trigger**: Instantly generate or regenerate metadata for single images from the Media Library.
* **Cron Bulk Generator**: Schedule batch processing of pending images with customizable batch sizes and intervals.
* **Smart JSON Parser**: Extracts structured JSON response and fixes common syntax issues automatically.
* **OpenRouter Model Dropdown**: Dynamically queries and renders available OpenRouter models grouped by provider, highlighting vision-capable models.
* **Settings & Dashboard**: Clean modern layout with CSS animations, interactive toggles, and statistics dashboard.
* **Pro Feature Showcase Tab (Free Version)**: Dedicated, compliant read-only preview tab showcasing Pro capabilities with fully internationalized strings. Filter-decoupled via `aialtg_show_pro_tab`.
* **Help Tab Documentation (Free Version)**: Clean educational documentation focused on OpenRouter and general plugin usage. Extensible action hook `aialtg_settings_help_sections` allows active add-ons to inject their own gateway setup guides and documentation dynamically.
* **Privacy Policy Suggestion**: Registers privacy policy content via `wp_add_privacy_policy_content()` disclosing external data transfer to OpenRouter.
* **Path Traversal Protection**: Validates attached file paths against `wp_upload_dir()['basedir']` prior to reading local images for Base64 encoding.
* **Safe Error Handling**: Exception catch blocks log details to server logs while returning generic, safe error messages to clients.
* **Multi-Gateway API Connection (Pro Add-on)**: Premium direct API connections to OpenAI and Google Gemini APIs alongside OpenRouter, featuring dynamic settings API key fields and routing.
* **Generate Captions & Descriptions (Pro Add-on)**: Extends AI prompts and attaches image Captions and Descriptions alongside Alt Texts and Titles upon processing.
* **Save Generation Metadata (Pro Add-on)**: Saves generation source and timestamp to attachment metadata.
* **Auto-Generate on Upload (Pro Add-on)**: Automatically processes new images as soon as they are uploaded to the Media Library.
* **Media Library Bulk Action (Pro Add-on)**: Native WordPress bulk actions dropdown integration to batch process selected images.
* **Skip Existing Details (Pro Add-on)**: Granular field-level overwrite protection during background processing, decoupled via `aialtg_skip_image_generation( $skip, $post_id, $source, $options )`.
* **WP-CLI Commands (Pro Add-on)**: Command-line integration (`wp kookoo-alt-text process`) to trigger image processing or background batching directly from the terminal via public APIs.

## Verification Commands
Validate PHP syntax of the codebase:
```bash
rtk php -l kookoo-ai-alt-text-creator.php
rtk php -l includes/class-aialtg-settings.php
rtk php -l includes/class-aialtg-generator.php
rtk php -l includes/class-aialtg-cron.php
rtk php -l uninstall.php
rtk php -l ../kookoo-ai-alt-text-creator-pro-addon/kookoo-ai-alt-text-creator-pro-addon.php
rtk php -l ../kookoo-ai-alt-text-creator-pro-addon/includes/class-aialtg-cli.php
rtk php -l ../kookoo-ai-alt-text-creator-pro-addon/uninstall.php
```
