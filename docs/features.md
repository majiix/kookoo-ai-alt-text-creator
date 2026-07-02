# Features

## Core Functionality
- **AI Alt Text & Title Generation**: Automatically analyzes images and writes SEO-optimized Alt Texts and Post Titles.
- **Manual Generation**: Adds button trigger to the Media Library list view column.
- **Background Bulk Generation (Cron)**: Enqueues unprocessed images and runs automated generation batches in the background.

## UI & Configuration
- **Settings Dashboard**: Premium two-column Modern SaaS layout with indigo/violet gradients, custom focus glow effects, and glassmorphic cards.
- **OpenRouter Model Selector**: Fetches available models dynamically from OpenRouter API with lazy skeleton loader.
- **Batch Processing Settings**: Configure batch size and execution interval.
- **Interactive Toggles**: Custom toggles with bouncy spring physics animations.
- **API Key Field**: OpenRouter API key field with show/hide password visibility toggle.
- **Control Center (Sidebar)**:
  - **Stats & Progress Card**: Real-time progress bar and stats (Total, Processed, Failed, Pending images).
  - **Milestone Celebration**: Triggers a custom canvas-based confetti shower when 100% complete, featuring an interactive manual retry button.
  - **Reset Progress**: Resets all processing flags.
  - **Retry Failed**: Resets status of failed image requests.
  - **Fix JSON Errors**: Recovers metadata where raw JSON was mistakenly saved as Alt Text/Title.
  - **Quick Help Box**: Helpful step-by-step documentation resources.
- **Edit Attachment Details**: Adds a meta box to the attachment edit screen showing generation timestamp, source, and error log logs.
