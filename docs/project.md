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
* [kookoo-ai-alt-text-creator.php](../kookoo-ai-alt-text-creator.php): Plugin bootstrap, hooks registration, public API helper (`aialtg_process_image`), Media Library custom column and row action ("AI Info"), privacy policy suggester (`register_privacy_policy_content`), and error-masked AJAX handlers controller. Automatic textdomain loading is handled via core WordPress 4.6+ directory standards.
* [includes/class-aialtg-settings.php](class-aialtg-settings.php): Handles settings page registration, sanitization, dashboard interface rendering, OpenRouter API key configuration, prompt configuration for alt text and titles, Pro upgrade sidebar showcase card, and integrated Help and setup documentation.
* [includes/class-aialtg-generator.php](class-aialtg-generator.php): Core logic for generating Alt Texts and Titles, performing remote requests to OpenRouter, uploads-directory validated Base64 conversions, always-on generation metadata logging, and robust JSON parsing.
* [includes/class-aialtg-cron.php](class-aialtg-cron.php): Handles automated background queue processing via WP Cron with configurable batch limits and host timeout advisories.
* [uninstall.php](../uninstall.php): Handles uninstallation cleanup, clearing scheduled background cron events and removing transients (`aialtg_stats`, `aialtg_openrouter_models`) while safely preserving settings and generation postmeta in the database for continuity across reinstalls.
* [docs/design_system.md](design_system.md): Detailed specification of the plugin visual tokens, typography, layouts, components, and animations.
* [.gitattributes](../.gitattributes): Packaging rules to exclude development files and documentation (`docs/`, `.codegraph/`) from release archives and SVN exports.

## Assumptions & Decisions
* **Free Version Scope**: The free edition is streamlined to Alt Text and Title generation via OpenRouter. Advanced features (Captions, Descriptions, Auto-Generate on Upload, Media Library Bulk Actions dropdown, Overwrite Protection, Direct OpenAI/Gemini APIs, and WP-CLI) are reserved for the Pro edition.
* **Separation of Concerns**: Free version is completely clean of Pro code, Pro conflict detection, legacy addon migration, and dead filter gates. Mutual exclusion is delegated exclusively to the Pro plugin. Scope of fixes is strictly limited to the Free plugin.
* **Dedicated OpenRouter Setup**: Free plugin operates directly with OpenRouter; redundant multi-gateway dropdowns, dead filter hooks, and the unused `api_gateway` option key are removed.
* **Generation Info Always On**: Generation metadata (`_aialtg_gen_date`, `_aialtg_gen_source`) is recorded automatically and displayed in the attachment edit screen meta box for manual and cron runs.
* **Media Library Actions**: The single-item "Generate Info" / "Regenerate" button and "AI Info" column in the Media Library list table are preserved for convenient manual generation.
* **WordPress Core Target**: Tested up to `7.1` maintained per user direction.
* **Author & Contributor Handles**: Maintained as `micromax` (Author header) and `micromax2` (Contributors list) per user direction.
* **External Services Disclosure**: Explicitly discloses OpenRouter (`https://openrouter.ai/`) in `readme.txt` and privacy policy suggestions.
* **Safe Uninstall Preservation**: Settings and postmeta are preserved in `uninstall.php` per user direction to prevent wiping active user configuration or image metadata upon reinstall or update.
* **Neutral Upsell Signatures**: Pro upsell card in settings uses neutral phrasing for WP-CLI tools without exposing `-pro` command signatures.
* **Strict WPCS Compliance**: Background cron execution time limit extension avoids error suppression operators.
* **Clean Free Reachability**: All leftover signatures from Pro overwrite rules, redundant `$option_name` instance properties, unused `$old_options` variable assignments, obsolete PHP 5.3 runtime checks (`safe_mode`), and dead/redundant CSS rules (`.aialtg-help-content .description`, `.aialtg-status-area.processing`) have been completely removed for net-negative LOC. Statistics grid selectors are aligned with `.aialtg-stat-item` markup.

## Current Features
* **OpenRouter AI Generation**: Descriptive Alt Text and Title generation using OpenRouter vision models (Gemini 2.5 Flash, GPT-4o, Claude 3.5, and free community models).
* **Manual One-Click Trigger**: Instantly generate or regenerate Alt Text and Title for individual images from the Media Library list view.
* **Cron Bulk Generator**: Schedule batch processing of pending images with customizable batch sizes and intervals.
* **Generation Metadata Logging**: Always records timestamps and execution sources (`manual`, `cron`) displayed on the attachment edit screen.
* **Dynamic OpenRouter Models**: Automatically fetches vision models from OpenRouter with custom model input support.
* **Smart JSON Parser**: Extracts structured JSON response and repairs common formatting issues automatically.
* **Settings & Dashboard**: Clean modern layout with CSS animations, interactive toggles, statistics dashboard, and Upgrade to Pro showcase card.
* **Pro Upgrade Showcase**: Sidebar card advertising Pro edition capabilities (Direct OpenAI & Gemini direct keys with zero platform fees, Complete 4-Field Metadata including Captions & Descriptions, Instant Upload Automation, Media Library 1-click Bulk Actions, Smart Overwrite Rules, and Developer WP-CLI Tools) linking to the Pro purchase page (https://violo.ir/?p=14).
* **Privacy Policy Suggestion**: Registers privacy policy content via `wp_add_privacy_policy_content()` disclosing external data transfer to OpenRouter.
* **Path Traversal Protection**: Validates attached file paths against `wp_upload_dir()['basedir']` prior to reading local images for Base64 encoding.
* **Safe Error Handling**: Masked error reporting prevents sensitive stack traces or internal details from leaking to clients.

## Verification Commands
Validate PHP syntax of the codebase:
```bash
rtk php -l kookoo-ai-alt-text-creator.php
rtk php -l includes/class-aialtg-settings.php
rtk php -l includes/class-aialtg-generator.php
rtk php -l includes/class-aialtg-cron.php
rtk php -l uninstall.php
```
