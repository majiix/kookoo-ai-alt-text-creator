# KooKoo AI Alt Text Creator - Project Documentation

## Overview
KooKoo AI Alt Text Creator is a WordPress plugin that automatically generates descriptive Alt Texts and Titles for images in the Media Library using AI models aggregated by OpenRouter. It offers both manual, one-click generation and background bulk generation via WordPress Cron scheduler.

## Tech Stack
* **PHP**: 7.4+ (Compatible with PHP 8.0+)
* **WordPress**: 6.0+ (Compatible up to 7.1.0)
* **API**: OpenRouter Chat Completions API (using WP HTTP API)
* **Assets**: Vanilla CSS & JavaScript / jQuery (for settings panel animations, skeleton loaders, and AJAX actions)

## Dependencies
* None (uses native WordPress APIs)

## Architecture
* [kookoo-ai-alt-text-creator.php](../kookoo-ai-alt-text-creator.php): Plugin bootstrap, hooks registration, and AJAX handlers controller.
* [includes/class-aialtg-settings.php](class-aialtg-settings.php): Handles settings page registration, sanitization, dashboard interface rendering, extensible Pro settings hooks (`aialtg_register_settings_fields`), and compliant Pro feature showcase preview tab.
* [includes/class-aialtg-generator.php](class-aialtg-generator.php): Core logic for generating Alt Texts and Titles, performing remote requests to OpenRouter, converting local images to Base64, parsing JSON, and firing extensibility hooks for Pro add-on processing.
* [includes/class-aialtg-cron.php](class-aialtg-cron.php): Handles automated background queue processing via WP Cron with configurable batch limits and host timeout advisories.
* [docs/design_system.md](design_system.md): Detailed specification of the plugin's visual tokens, typography, layouts, components, and animations.
* [kookoo-ai-alt-text-creator-pro-addon/kookoo-ai-alt-text-creator-pro-addon.php](../kookoo-ai-alt-text-creator-pro-addon/kookoo-ai-alt-text-creator-pro-addon.php): Pro add-on bootstrap, registers Pro settings fields into functional tabs, implements Direct API gateways (OpenAI, Gemini), caption and description generation, generation metadata logging, upload hooks, and bulk actions.
* [kookoo-ai-alt-text-creator-pro-addon/includes/class-aialtg-cli.php](../kookoo-ai-alt-text-creator-pro-addon/includes/class-aialtg-cli.php): Registers premium WP-CLI commands (`wp kookoo-alt-text process`).
* [kookoo-ai-alt-text-creator-pro-addon/uninstall.php](../kookoo-ai-alt-text-creator-pro-addon/uninstall.php): Handles uninstallation cleanup for the Pro add-on, removing premium option keys from the settings array.


## Current Features
* **AI Generation**: Descriptive Alt Text and Title generation with custom vision/image models.
* **Manual Trigger**: Instantly generate or regenerate metadata for single images from the Media Library.
* **Cron Bulk Generator**: Schedule batch processing of pending images with customizable batch sizes and intervals.
* **Smart JSON Parser**: Extracts structured JSON response and fixes common syntax issues automatically.
* **OpenRouter Model Dropdown**: Dynamically queries and renders available OpenRouter models grouped by provider, highlighting vision-capable models.
* **Settings & Dashboard**: Clean modern layout with CSS animations, interactive toggles, and statistics dashboard.
* **Pro Feature Showcase Tab (Free Version)**: Dedicated, compliant read-only preview tab showcasing Pro capabilities and features to free users without dummy or disabled form controls. Automatically hidden when Pro add-on is active.
* **Contextual Pro Tip Callouts (Free Version)**: Subtle, accessible callout boxes below visible features that have enhanced Pro counterparts (API Gateways, Captions/Descriptions, Overwrite Protection). Hidden automatically when Pro add-on is active.
* **Pro Dynamic Settings Injection (Pro Add-on)**: Injects active Pro configuration controls directly into General, Generation Options, and Bulk Generation tabs using native WordPress settings API hooks (`aialtg_register_settings_fields`).
* **Multi-Gateway API Connection (Pro Add-on)**: Premium direct API connections to OpenAI and Google Gemini APIs alongside OpenRouter, featuring dynamic settings API key fields and routing.
* **Generate Captions & Descriptions (Pro Add-on)**: Extends AI prompts and attaches image Captions and Descriptions alongside Alt Texts and Titles upon processing.
* **Save Generation Metadata (Pro Add-on)**: Saves generation source and timestamp to attachment metadata.
* **Auto-Generate on Upload (Pro Add-on)**: Automatically processes new images as soon as they are uploaded to the Media Library.
* **Media Library Bulk Action (Pro Add-on)**: Native WordPress bulk actions dropdown integration to batch process selected images.
* **Skip Existing Alt Texts (Pro Add-on)**: Skip generating Alt Text for images that already have one during background processing.
* **WP-CLI Commands (Pro Add-on)**: Command-line integration (`wp kookoo-alt-text process`) to trigger image processing or background batching directly from the terminal.
* **Pro Version Compatibility Verification**: Verifies version synchronization between the main plugin and the Pro add-on to prevent mismatches and conflicts.
* **API Key Setup Guides & Help Resources**: Detailed in-dashboard step-by-step setup guides, direct links, and troubleshooting notes for OpenRouter, OpenAI Direct, and Google Gemini Direct gateways in the Help tab.

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
