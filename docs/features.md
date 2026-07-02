# Features

## Core Functionality
- **AI Alt Text & Title Generation**: Automatically analyzes images and writes SEO-optimized Alt Texts and Post Titles.
- **Manual Generation**: Adds button trigger to the Media Library list view column.
- **Background Bulk Generation (Cron)**: Enqueues unprocessed images and runs automated generation batches in the background.

## UI & Configuration
- **Settings Page**: Dedicated dashboard in WordPress admin Settings menu.
- **OpenRouter Model Selector**: Fetches available models dynamically from OpenRouter API with lazy skeleton loader.
- **Batch Processing Settings**: Configure batch size and execution interval.
- **Stats Card**: Real-time progress bar and stats (Total, Processed, Failed, Pending images).
- **Control Panel**:
  - **Reset Progress**: Resets all processing flags.
  - **Retry Failed**: Resets status of failed image requests.
  - **Fix JSON Errors**: Recovers metadata where raw JSON was mistakenly saved as Alt Text/Title.
- **Edit Attachment Details**: Adds a meta box to the attachment edit screen showing generation timestamp, source, and error log logs.
