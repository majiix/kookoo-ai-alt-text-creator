# Project Overview

KooKoo AI Alt Text Creator is a WordPress plugin that automatically generates alt text and titles for images using the OpenRouter AI API.

## Tech Stack
- **Core**: PHP (PHP 7.4+ compatible, tested on PHP 8.3)
- **WordPress**: WordPress 6.0+ compatible
- **API**: OpenRouter Chat Completions API
- **Assets**: Vanilla JS / jQuery (admin), CSS (admin settings UI)

## Architecture
- `kookoo-ai-alt-text-creator.php`: Plugin bootstrap and AJAX controller.
- `includes/class-aialtg-settings.php`: Settings API configuration page and stats layout.
- `includes/class-aialtg-generator.php`: Core generator class responsible for fetching images, converting local URLs to Base64, requesting content from OpenRouter, robust JSON extraction, and meta updates.
- `includes/class-aialtg-cron.php`: Background worker processor using WP Cron scheduler.

## Verification Commands
Validate syntax of all plugin files:
```bash
php -l kookoo-ai-alt-text-creator.php
php -l includes/class-aialtg-settings.php
php -l includes/class-aialtg-generator.php
php -l includes/class-aialtg-cron.php
php -l uninstall.php
```
