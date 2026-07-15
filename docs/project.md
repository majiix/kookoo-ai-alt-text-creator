# KooKoo AI Alt Text Creator - Project Documentation

## Overview
KooKoo AI Alt Text Creator is a WordPress plugin that automatically generates descriptive Alt Texts and Titles for images in the Media Library using AI models aggregated by OpenRouter. It offers both manual, one-click generation and background bulk generation via WordPress Cron scheduler.

## Tech Stack
* **PHP**: 7.4+ (Compatible with PHP 8.0+)
* **WordPress**: 6.0+ (Compatible up to 7.0)
* **API**: OpenRouter Chat Completions API (using WP HTTP API)
* **Assets**: Vanilla CSS & JavaScript / jQuery (for settings panel animations, skeleton loaders, and AJAX actions)

## Dependencies
* None (uses native WordPress APIs)

## Architecture
* [kookoo-ai-alt-text-creator.php](../kookoo-ai-alt-text-creator.php): Plugin bootstrap, hooks registration, and AJAX handlers controller.
* [includes/class-aialtg-settings.php](class-aialtg-settings.php): Handles settings page registration, sanitization, and dashboard interface rendering.
* [includes/class-aialtg-generator.php](class-aialtg-generator.php): Core logic for generating Alt Texts and Titles, performing remote requests to OpenRouter, converting local images to Base64, parsing JSON, and saving metadata.
* [includes/class-aialtg-cron.php](class-aialtg-cron.php): Handles automated background queue processing via WP Cron.
* [includes/class-aialtg-licensing.php](class-aialtg-licensing.php): [NEW] Handles EDD Software Licensing communication, license activation, deactivation, daily check cron status, and local caching.
* [docs/design_system.md](design_system.md): [NEW] Detailed specification of the plugin's visual tokens, typography, layouts, components, and animations.
* [kookoo-ai-alt-text-creator-pro-addon/kookoo-ai-alt-text-creator-pro-addon.php](../kookoo-ai-alt-text-creator-pro-addon/kookoo-ai-alt-text-creator-pro-addon.php): [NEW] Pro addon plugin main file, implements premium filtering logic to skip existing alt texts.


## Current Features
* **AI Generation**: Descriptive Alt Text and Title generation with custom vision/image models.
* **Manual Trigger**: Instantly generate or regenerate metadata for single images from the Media Library.
* **Cron Bulk Generator**: Schedule batch processing of pending images.
* **Smart JSON Parser**: Extracts structured JSON response and fixes common syntax issues automatically.
* **OpenRouter Model Dropdown**: Dynamically queries and renders available OpenRouter models grouped by provider, highlighting vision-capable models.
* **Settings & Dashboard**: Premium modern layout with CSS animations, interactive toggles, and statistics dashboard.
* **EDD Software Licensing**: [NEW] Premium key entry field, activation, deactivation, and verification status check using EDD Software Licensing API.
* **Save Generation Metadata**: [NEW] Premium option to save generation source and timestamp to attachment metadata.
* **Skip Existing Alt Texts**: [NEW] Premium option (located under the Bulk Generation tab) to skip generating Alt Text for images that already have one (written manually or by other plugins) during background processing.
* **Pro Version Compatibility Verification**: [NEW] Automatically verifies version synchronization between the main plugin and the Pro addon to prevent mismatches and conflicts. If the Pro addon is outdated, it disables premium features and displays a warning to download updates.

## Verification Commands
Validate PHP syntax of the codebase:
```bash
php -l kookoo-ai-alt-text-creator.php
php -l includes/class-aialtg-settings.php
php -l includes/class-aialtg-generator.php
php -l includes/class-aialtg-cron.php
php -l includes/class-aialtg-licensing.php
php -l uninstall.php
php -l ../kookoo-ai-alt-text-creator-pro-addon/kookoo-ai-alt-text-creator-pro-addon.php
```
