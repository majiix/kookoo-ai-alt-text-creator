# KooKoo AI Alt Text Creator - Project Documentation

## Overview
KooKoo AI Alt Text Creator is a completely free WordPress plugin that automatically generates descriptive Alt Texts and Titles for images in the Media Library using AI models via OpenRouter. It offers manual one-click single-image generation from the Media Library list table and automated background processing via WordPress Cron scheduler.

## Tech Stack
* **PHP**: 7.4+ (Enforced via `Requires PHP: 7.4` header, compatible with PHP 8.0+)
* **WordPress**: 6.0+ (Tested up to 7.1.0)
* **APIs**: OpenRouter API (via WordPress HTTP API)
* **Assets**: Vanilla CSS & JavaScript / jQuery (for settings panel animations, skeleton loaders, and AJAX actions)

## Dependencies
* None (uses native WordPress APIs)

## Architecture
* [kookoo-ai-alt-text-creator.php](../kookoo-ai-alt-text-creator.php): Plugin bootstrap, hooks registration, lifecycle event dispatch (`aialtg_loaded`), public API helper (`aialtg_process_image`), batch action hook (`aialtg_process_batch`), Media Library custom column and row action ("AI Details"), textdomain loader (`load_plugin_textdomain`), privacy policy suggester (`register_privacy_policy_content`), mutual conflict resolution with Pro (`check_plugin_conflicts`, `activation_logic`, `deactivation_advisory_notices`), and error-masked AJAX handlers controller.
* [includes/class-aialtg-settings.php](class-aialtg-settings.php): Handles settings page registration, sanitization, dashboard interface rendering, OpenRouter gateway configuration, prompt configuration for alt text and titles, and integrated Help and setup documentation.
* [includes/class-aialtg-generator.php](class-aialtg-generator.php): Core logic for generating Alt Texts and Titles, performing remote requests to OpenRouter, uploads-directory validated Base64 conversions, always-on generation metadata logging, and robust JSON parsing.
* [includes/class-aialtg-cron.php](class-aialtg-cron.php): Handles automated background queue processing via WP Cron with configurable batch limits and host timeout advisories.
* [uninstall.php](../uninstall.php): Handles uninstallation cleanup, removing plugin options (`aialtg_settings`), transients, and internal generation postmeta.
* [docs/design_system.md](design_system.md): Detailed specification of the plugin visual tokens, typography, layouts, components, and animations.
* [.gitattributes](../.gitattributes): Packaging rules to exclude development files and documentation (`docs/`, `.codegraph/`) from release archives and SVN exports.

## Assumptions & Decisions
* **Free Version Scope**: The free edition is streamlined to Alt Text and Title generation via OpenRouter. Advanced features (Captions, Descriptions, Auto-Generate on Upload, Media Library Bulk Actions dropdown, Overwrite Protection, Direct OpenAI/Gemini APIs, and WP-CLI) are reserved for the Pro edition.
* **Separation of Concerns**: Free version is completely clean of Pro code, legacy addon migration, and pro database options. It strictly configures and uses OpenRouter.
* **Generation Info Always On**: The settings toggle for saving generation metadata is removed, and generation metadata (`_aialtg_gen_date`, `_aialtg_gen_source`) is always recorded automatically and displayed in the attachment edit screen meta box.
* **Media Library Actions**: The single-item "Generate Details" button and "AI Details" column in the Media Library list table are preserved for convenient manual generation, while the bulk action dropdown item is removed.
* **Graceful Database Fallback**: If an existing installation has `openai` or `gemini` recorded as `api_gateway`, it gracefully defaults to `openrouter` during generation and settings sanitization.
* **No Direct Gateway Cards in Free**: All UI cards, direct gateway model presets, and setup instructions for OpenAI Direct and Gemini Direct are cleanly removed from the free version UI and Help tabs.
* **WordPress Core Target**: Tested up to `7.1` maintained per user direction.
* **Author & Contributor Handles**: Maintained as `micromax` (Author header) and `micromax2` (Contributors list) per user direction.
* **External Services Disclosure**: Explicitly discloses OpenRouter (`https://openrouter.ai/`) in `readme.txt` and privacy policy suggestions.

## Current Features
* **OpenRouter AI Generation**: Descriptive Alt Text and Title generation using OpenRouter vision models (Gemini 2.5 Flash, GPT-4o, Claude 3.5, and free community models).
* **Manual One-Click Trigger**: Instantly generate or regenerate Alt Text and Title for individual images from the Media Library list view.
* **Cron Bulk Generator**: Schedule batch processing of pending images with customizable batch sizes and intervals.
* **Generation Metadata Logging**: Always records timestamps and execution sources (`manual`, `cron`) displayed on the attachment edit screen.
* **Dynamic OpenRouter Models**: Automatically fetches vision models from OpenRouter with custom model input support.
* **Smart JSON Parser**: Extracts structured JSON response and repairs common formatting issues automatically.
* **Settings & Dashboard**: Clean modern layout with CSS animations, interactive toggles, and statistics dashboard.
* **Privacy Policy Suggestion**: Registers privacy policy content via `wp_add_privacy_policy_content()` disclosing external data transfer to OpenRouter.
* **Path Traversal Protection**: Validates attached file paths against `wp_upload_dir()['basedir']` prior to reading local images for Base64 encoding.
* **Safe Error Handling**: Masked error reporting prevents sensitive stack traces or internal details from leaking to clients.
* **Mutual Pro Deactivation**: Automatically deactivates the Pro version upon activation and safely yields to Pro during runtime if both are active.

## Verification Commands
Validate PHP syntax of the codebase:
```bash
rtk php -l kookoo-ai-alt-text-creator.php
rtk php -l includes/class-aialtg-settings.php
rtk php -l includes/class-aialtg-generator.php
rtk php -l includes/class-aialtg-cron.php
rtk php -l uninstall.php
```
