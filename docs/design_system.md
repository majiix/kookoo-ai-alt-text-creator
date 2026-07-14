# KooKoo AI Alt Text Creator - Design System

This document outlines the visual identity, tokens, layout grid, CSS classes, and UI components used in the settings and administration pages of the KooKoo AI Alt Text Creator WordPress plugin. Refer to this specification when expanding the UI or designing new admin pages to maintain consistency and premium aesthetics.

---

## 1. Design Philosophy

The KooKoo AI Alt Text Creator settings UI is designed to feel:
- **Premium & Modern**: Avoids standard, uninspired flat WordPress admin controls.
- **Dynamic & Responsive**: Incorporates micro-interactions, smooth CSS transitions, and fluid grid systems that adapt from desktop down to mobile.
- **Glassmorphism-Inspired**: Uses light borders, subtle backdrops, and soft shadows to create visual layers.

---

## 2. Design Tokens (CSS Variables)

All key style values are defined as CSS custom properties under `:root` in [admin.css](/assets/css/admin.css).

### Color Palette

| Token | CSS Variable | Hex Value | Purpose / Usage |
| :--- | :--- | :--- | :--- |
| **Primary** | `--aialtg-primary` | `#6366f1` | Primary actions, branding, key accents, focus rings (Indigo 500) |
| **Primary Hover** | `--aialtg-primary-hover` | `#4f46e5` | Hover state for primary buttons and interactive elements |
| **Primary Light** | `--aialtg-primary-light` | `#e0e7ff` | Light background for button hovers, subtle highlights |
| **Secondary** | `--aialtg-secondary` | `#818cf8` | Secondary brand color, gradient mixes |
| **Accent** | `--aialtg-accent` | `#a855f7` | Dynamic accents (e.g. side-borders, confetti, decorations) |
| **Background** | `--aialtg-bg` | `#f8fafc` | Main wrapper background |
| **Card Background** | `--aialtg-card-bg` | `rgba(255, 255, 255, 0.9)` | Glassmorphic base for form tables and sidebar cards |
| **Text** | `--aialtg-text` | `#1e293b` | Primary body text (Slate 800) |
| **Text Muted** | `--aialtg-text-muted` | `#64748b` | Subtitles, labels, helpers, secondary text (Slate 500) |
| **Border** | `--aialtg-border` | `#e2e8f0` | Form inputs, card outlines, separators |
| **Success** | `--aialtg-success` | `#10b981` | Success alerts, completed stats, positive status badges |
| **Error** | `--aialtg-error` | `#ef4444` | Errors, failed actions, red warning indicators |

### Geometry & Animation Tokens

- **Border Radius**: `--aialtg-radius: 12px` (used on cards, headers, major sections).
- **Default Shadow**: `--aialtg-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.04), 0 4px 6px -4px rgba(99, 102, 241, 0.04), ...`
- **Hover Shadow**: `--aialtg-shadow-hover: 0 20px 25px -5px rgba(99, 102, 241, 0.08), 0 8px 10px -6px rgba(99, 102, 241, 0.08)`
- **Easing Curve**: `--aialtg-ease: cubic-bezier(0.4, 0, 0.2, 1)` (standardized cubic-bezier for smooth interactions).

---

## 3. Typography & Spacing

- **Font Family**: `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif` (system font stack with fallbacks).
- **Titles**:
  - Main Heading (`h1`): `26px`, bold (`800`), white in the header.
  - Section Heading (`h2`): `18px`, bold (`700`), `#0f172a`.
  - Sidebar Heading (`h3`): `16px`, bold (`700`), uppercase, `var(--aialtg-primary)`.
- **Body & Captions**:
  - Settings Labels / Primary text: `13px`.
  - Muted Descriptions (`.aialtg-section-desc`): `13px`, line-height `1.5`, colored via `--aialtg-text-muted`.
  - Footnotes / Badges: `11px` to `12px`.

---

## 4. Page Layout Grid

The main dashboard is constructed as a two-column responsive grid:

```
+-------------------------------------------------------------+
|                     .aialtg-header                          |
+-------------------------------------------------------------+
+----------------------------------------+ +------------------+
|          .aialtg-main-content          | | .aialtg-sidebar  |
|                                        | |                  |
|  +----------------------------------+  | |  +------------+  |
|  |           .form-table            |  | |  | .aialtg-   |  |
|  |           (Main Card)            |  | |  |  stats-card|  |
|  +----------------------------------+  | |  +------------+  |
|                                        | |                  |
|  +----------------------------------+  | |  +------------+  |
|  |           .form-table            |  | |  | .aialtg-   |  |
|  |           (Main Card)            |  | |  |  license-  |  |
|  +----------------------------------+  | |  |  card      |  |
|                                        | |  +------------+  |
+----------------------------------------+ +------------------+
```

### Layout Classes
- **`.aialtg-wrapper`**: Outer container wrapping the entire admin page structure. Sets max-width to `1100px` and custom margins.
- **`.aialtg-header`**: Top block. Colored with a linear gradient of `--aialtg-primary` to `--aialtg-secondary`, containing a radial background circle effect.
- **`.aialtg-dashboard-layout`**: Sets up the 2-column grid (`grid-template-columns: 1fr 340px`).
- **Responsive Collapsing**: Automatically collapses the sidebar under the main area at widths $\le$ `900px` (`grid-template-columns: 1fr`).

---

## 5. UI Components

### A. Cards & Containers
- **Main settings sections** style the standard WordPress `.form-table` to look like a cards container:
  - Background is white translucent (`rgba(255, 255, 255, 0.9)`) combined with a blur backdrop-filter (`blur(8px)`).
  - Outlined with `--aialtg-border` and given `--aialtg-radius` corners.
  - Hover states transition the box-shadow to `--aialtg-shadow-hover` and border color to a soft primary glow.
- **Sidebar cards** use the `.aialtg-card` class, mirroring the visual layout of main cards.
- **Stats Card (`.aialtg-stats-card`)**: Enhances standard cards by drawing a `5px` vertical linear gradient highlight along the left border.

### B. Form Inputs
- **Inputs & Dropdowns**: Applied to standard text fields, passwords, numbers, and custom selects (`#aialtg-model-select`). Features a minimal border, interior shadow, and soft hover/focus animations.
- **Bouncy Toggle Switch**:
  - Wrap structure: `.aialtg-toggle`
  - Leverages `.aialtg-toggle-slider` and a hidden checkbox input to create a spring-like toggle action when clicked (`transform: translateX(...)`).

### C. Statistics Grid & Progress
- **Stats Block (`.aialtg-stats-grid`)**: A structured grid showing high-level image metadata processing.
- **Progress Bar (`.aialtg-progress-bar`)**: Contains `.aialtg-progress-fill` displaying batch tasks completion percentages, styled with a linear-gradient.
- **Milestone Celebration (`.aialtg-milestone-wrap`)**: An alert box that bounces in when processing hits 100%. Paired with `#aialtg-confetti-canvas` for fireworks animations.

### D. Licensing Controls
- **Licensing Card (`.aialtg-license-card`)**: Holds fields for entering the license key.
- **Password Mask Visibility**: Inside the key input, `.aialtg-toggle-license-visibility` toggles the input field type between `password` and `text`.
- **Status Badges (`.aialtg-badge`)**: Small inline indicators:
  - Active: `.aialtg-badge-success`
  - Inactive / Invalid: `.aialtg-badge-error`
  - Pending: `.aialtg-badge-neutral`

---

## 6. CSS Transitions & Animations

CSS classes leverage micro-animations for high-fidelity interactive feedback:

1. **`aialtgBounceIn`**: Bounces in the milestone notification:
   ```css
   @keyframes aialtgBounceIn {
       0% { opacity: 0; transform: scale(0.8); }
       70% { transform: scale(1.05); }
       100% { opacity: 1; transform: scale(1); }
   }
   ```
2. **`aialtgRotateStar`**: Infinitely rotates and scales the celebrate dashboard star:
   ```css
   @keyframes aialtgRotateStar {
       0%, 100% { transform: rotate(0deg) scale(1); }
       50% { transform: rotate(15deg) scale(1.15); }
   }
   ```
3. **`aialtgFadeIn`**: Reveals AJAX statuses (`.aialtg-status-area`) by moving them slightly down and fading in.
4. **`aialtg-skeleton-animation`**: Translates a gradient shimmer overlay across skeleton loaders.

---

## 7. HTML Markup Reference

Here is a typical HTML markup example combining these components:

```html
<div class="aialtg-wrapper">
    <!-- Header Area -->
    <header class="aialtg-header">
        <h1>KooKoo AI Alt Text Creator</h1>
        <p class="aialtg-subtitle">Descriptive Alt Text generator powered by OpenRouter</p>
    </header>

    <div class="aialtg-dashboard-layout">
        <!-- Main Form Content -->
        <main class="aialtg-main-content">
            <form method="post" action="options.php">
                <div class="form-table">
                    <h2>General Options</h2>
                    <p class="aialtg-section-desc">Customize metadata generation rules.</p>

                    <div class="aialtg-toggle">
                        <label>
                            <input type="checkbox" name="option_key" value="1" />
                            <span class="aialtg-toggle-slider"></span>
                            <span class="aialtg-toggle-label">Enable Auto-Generation</span>
                        </label>
                    </div>
                </div>
            </form>
        </main>

        <!-- Sidebar Components -->
        <aside class="aialtg-sidebar">
            <!-- Stats Panel -->
            <div class="aialtg-card aialtg-stats-card">
                <div class="aialtg-card-header">
                    <h3>Status Overview</h3>
                </div>
                <div class="aialtg-stats-grid">
                    <div class="aialtg-stat-item">
                        <span class="aialtg-stat-label">Total Images</span>
                        <span class="aialtg-stat-value">124</span>
                    </div>
                    <div class="aialtg-stat-item">
                        <span class="aialtg-stat-label">Processed</span>
                        <span class="aialtg-stat-value success">98</span>
                    </div>
                </div>

                <div class="aialtg-progress-bar-wrap">
                    <div class="aialtg-progress-bar">
                        <div class="aialtg-progress-fill" style="width: 79%;"></div>
                    </div>
                    <span class="aialtg-progress-text">79% Complete</span>
                </div>
            </div>

            <!-- Licensing Panel -->
            <div class="aialtg-card aialtg-license-card">
                <div class="aialtg-card-header">
                    <h3>License Key</h3>
                </div>
                <div class="aialtg-license-input-group">
                    <div class="aialtg-input-wrap aialtg-password-wrap aialtg-license-input-inner">
                        <input type="password" id="aialtg-license-key" placeholder="Enter key..." />
                        <button type="button" class="aialtg-toggle-password">👁</button>
                    </div>
                    <button type="button" id="aialtg-activate-license-btn" class="button button-primary">Activate</button>
                </div>
                <div class="aialtg-license-status-wrap">
                    <span class="aialtg-badge aialtg-badge-success">Active</span>
                </div>
            </div>
        </aside>
    </div>
</div>
```
