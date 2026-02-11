# Magix Text Pro

**Magix Text Pro** is a WordPress plugin that lets you combine a fixed text with rotating (animated) texts and an optional suffix.

It provides an admin panel to create “Magix Text” presets and a shortcode to render them on any page.

## Features

- Fixed text + rotating texts (up to 5)
- Optional suffix text
- Separate color controls for fixed / rotating / suffix
- Bold toggles for fixed / rotating / suffix
- Font size (px) and font family selection
- Text alignment: left / center / right
- Animation duration and delay controls
- Shortcode output: `\[magix_text id="123"\]`
- Stores presets in a dedicated DB table: `wp_magix_text_pro`

## How it works

1. Create a preset in **WP Admin → Magix Text Pro**
2. Copy the generated shortcode:
   - `\[magix_text id="X"\]`
3. Paste it into any page/post (or builder shortcode widget)

The frontend JavaScript calculates the maximum rotating text width so the layout does not “jump” while rotating.

## Installation

### Option A — WordPress Admin
1. Download this repository as ZIP
2. WordPress → Plugins → Add New → Upload Plugin
3. Upload ZIP → Install → Activate

### Option B — Manual
1. Upload the folder `magix-text-pro` to:
   - `wp-content/plugins/`
2. Activate the plugin from WordPress → Plugins

## Usage

Example:
```text
[magix_text id="1"]
