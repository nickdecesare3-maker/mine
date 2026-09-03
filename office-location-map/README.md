# Office Location Map

Adds an **Office Location** post type plus an `[office_map]` shortcode that plots offices by latitude/longitude on a fully re-colorable, geographically accurate world map. Clicking (or tapping) a marker opens a modal centered on screen with that office's name, full address, image, description and contact person details.

## Why an inline SVG map instead of Google Maps / Mapbox

The brief calls for choosing the map's **land, sea and border colors** -- something a raster tile provider (Google Maps, Mapbox, OpenStreetMap tiles) doesn't let you do, since you're displaying someone else's pre-rendered imagery. Instead, the plugin ships an accurate world map -- real coastlines and individual country borders, not a schematic approximation -- as inline SVG (`assets/images/world-map.svg`, equirectangular projection, `viewBox="0 0 1000 500"`), built from [Natural Earth](https://www.naturalearthdata.com/) 110m admin-0 countries data (public domain, no attribution required; see `build_world_svg.py` below for how it was generated, including antimeridian-safe clipping so Russia/Fiji/the USA don't tear across the map at the ±180° line). Because it's inlined directly into the page (not loaded via `<img src>`), the site's own CSS -- driven by the admin's color choices -- can style its `fill`/`stroke` directly, per country. Latitude/longitude are converted to an x/y percentage with the standard equirectangular formula (`olm_project_marker_position()`) and each marker is placed with plain CSS `left`/`top`, aligned to the same projection the map itself uses. No API key, no external requests, no JS mapping library, and no build step for the plugin itself.

## Features

- **Office Location** custom post type (`olm_office`): title (office name), featured image, content editor (description, WYSIWYG with images), plus a meta box for latitude/longitude and another for full address, contact person name, title, phone and email. Each office's edit screen has a ready-to-copy `[office_map ids="…"]` box in the sidebar for embedding just that one office.
- **`[office_map]`** shortcode: plots every published office (or a specific `ids="1,2,3"` subset) on the map.
- **Click-to-open info modal**: clicking or tapping a marker opens a centered modal (a native `<button>`, so Enter/Space activates it too); clicking the same marker again, its close button, the overlay, Escape, or a different marker all close it. Proper `role="dialog"`/`aria-modal`, a focus trap, and focus restoration to the marker on close.
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
  js/map.js                     Marker click → modal behavior (vanilla JS, no jQuery)
  images/world-map.svg          Bundled accurate world map (Natural Earth 110m countries, equirectangular), inlined at render time
bin/
  build_world_svg.py            Regenerates assets/images/world-map.svg from a Natural Earth countries GeoJSON (not run at plugin runtime; see below)
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
- No build step and no external PHP or JS dependencies for the plugin itself.

## Regenerating the world map

`assets/images/world-map.svg` is pre-built and checked in -- WordPress never needs to regenerate it. If you want to swap in a different resolution (Natural Earth also publishes 50m/10m for more coastline detail) or re-run the build after an upstream data update, `bin/build_world_svg.py` (stdlib-only Python 3, no pip packages) is the generator:

1. Download a Natural Earth admin-0 countries GeoJSON, e.g. `ne_110m_admin_0_countries.geojson` from the [Natural Earth Vector](https://github.com/nvkelso/natural-earth-vector) repo's `geojson/` folder (public domain), and save it alongside the script as `ne_110m_countries.geojson`.
2. Run `python3 bin/build_world_svg.py` from that folder. It projects every country (equirectangular, matching `olm_project_marker_position()` exactly), clips/splits any country crossing the ±180° antimeridian (Russia, Fiji, the USA, ...) so it doesn't tear across the map, and writes `world-map.svg` with one `<path class="olm-land">` per country piece plus a `<title>` for its name.
3. Copy the output over `assets/images/world-map.svg`.
