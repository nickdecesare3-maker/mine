# Office Location Map

Adds an **Office Location** post type plus an `[office_map]` shortcode that plots offices by latitude/longitude on a fully re-colorable, stylized world map. Hovering (or tapping, for touch/keyboard users) a marker opens a modal centered on screen with that office's name, full address, image, description and contact person details.

## Why a stylized SVG map instead of Google Maps / Mapbox

The brief calls for choosing the map's **land, sea and border colors** -- something a raster tile provider (Google Maps, Mapbox, OpenStreetMap tiles) doesn't let you do, since you're displaying someone else's pre-rendered imagery. Instead, the plugin ships its own simplified, low-poly world map as inline SVG (`assets/images/world-map.svg`, equirectangular projection, `viewBox="0 0 1000 500"`). Because it's inlined directly into the page (not loaded via `<img src>`), the site's own CSS -- driven by the admin's color choices -- can style its `fill`/`stroke` directly. Latitude/longitude are converted to an x/y percentage with the standard equirectangular formula (`olm_project_marker_position()`) and each marker is placed with plain CSS `left`/`top`. No API key, no external requests, no JS mapping library, and no build step.

## Features

- **Office Location** custom post type (`olm_office`): title (office name), featured image, content editor (description, WYSIWYG with images), plus a meta box for latitude/longitude and another for full address, contact person name, title, phone and email. Each office's edit screen has a ready-to-copy `[office_map ids="…"]` box in the sidebar for embedding just that one office.
- **`[office_map]`** shortcode: plots every published office (or a specific `ids="1,2,3"` subset) on the map.
- **Hover-to-open info modal**: mouseenter (or keyboard focus, for accessibility) on a marker opens a centered modal; moving off the marker (and not onto the modal) closes it again after a short delay. A click/tap "pins" the modal open -- needed since touch devices don't have real hover -- until it's closed via its close button, the overlay, Escape, or by opening a different marker. Proper `role="dialog"`/`aria-modal`, a focus trap, and focus restoration to the marker on close.
- **Map & Field Styling** admin page (Office Map → Map & Field Styling): color pickers for land, sea, border, marker and marker-hover colors, plus border width, marker size and the map's max width; modal background color, corner radius, max width, and image size/roundness; and independent font family, size, weight and color for every field shown in the modal -- office name, full address, description, contact person name, contact person title, contact person phone, and contact person email.
- **Getting Started notice**: shown once on Office Location admin screens, dismissible per-user, plus a permanent one-line `[office_map]` reminder above the Office Locations list.

## Requirements

- WordPress 5.8+
- PHP 7.4+

## Installation

1. Copy (or clone) this `office-location-map` folder into `wp-content/plugins/`.
2. Activate **Office Location Map** from the Plugins screen. That's it -- there is no build step.

## Usage

### 1. Add office locations

**Office Map → Add New**. Set the title (office name), latitude/longitude in the *Map Coordinates* box (decimal degrees, e.g. `40.7484` / `-73.9857` for New York City), the featured image, and fill in the *Office & Contact Details* box (full address, contact person name/title/phone/email). Write the description in the main content editor. The sidebar's *Shortcode* box shows `[office_map ids="…"]` for that one office, ready to copy.

### 2. Set the map and field styling (optional)

**Office Map → Map & Field Styling** controls, sitewide:

- **Map Colors**: land, sea, border colors and border width; marker and marker-hover colors and marker size; the map's max width.
- **Info Modal**: background color, corner radius, max width, and the office image's size and corner roundness (0 = square, 50 = circle).
- **Field Typography**: font family, size, weight and text color, set independently for the office name, full address, description, contact person name, contact person title, contact person phone and contact person email. Leave a field blank/"Theme default" to inherit your theme's normal styling.

### 3. Add the shortcode to a page

**Show every office:**

```
[office_map]
```

**Show only specific offices** (comma-separated Office Location post IDs, copied from each office's Shortcode box):

```
[office_map ids="12,45"]
```

## Plugin structure

```
office-location-map.php         Main plugin file (headers, bootstrap, hooks)
uninstall.php                   Non-destructive cleanup on plugin deletion
includes/
  functions.php                 Shared helpers (meta keys, lat/lng projection, marker + modal markup, data assembly)
  class-olm-post-type.php       `olm_office` CPT registration
  class-olm-meta-boxes.php      Coordinates + office/contact details meta boxes + the per-post shortcode box
  class-olm-shortcode.php       [office_map] shortcode handler
  class-olm-activation.php      Activation (flush rewrites) / deactivation
admin/
  class-olm-admin.php           Getting Started notice, shortcode reminder, notice dismissal
  class-olm-settings.php        Map & Field Styling page: map colors, modal styling, per-field typography; generates the inline CSS
  css/admin.css                 Settings page layout
  js/admin.js                   WP color picker init
assets/
  css/map.css                   Map container + marker layout
  css/modal.css                 Info modal styles
  js/map.js                     Marker hover/focus/click → modal behavior (vanilla JS, no jQuery)
  images/world-map.svg          Bundled stylized world map (equirectangular, low-poly), inlined at render time
languages/                      Text domain: office-location-map
```

## Internationalization

All user-facing strings use the `office-location-map` text domain and standard `__()`/`_e()`/`esc_html__()` calls. Generate a `.pot` file with WP-CLI once you have it available:

```bash
wp i18n make-pot . languages/office-location-map.pot
```

## Coding conventions

- Every function, class, hook, option, meta key and AJAX action is prefixed `olm_`/`Olm_` to avoid collisions.
- All output is escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` for the rich-text description); all input is sanitized on save (`sanitize_text_field`, `sanitize_email`, `sanitize_hex_color`) and nonce-checked.
- No build step and no external PHP or JS dependencies.
