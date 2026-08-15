# Team Roster for Divi

Adds a **Team Member** post type plus two native Divi 5 blocks -- **Team Member Card** and **Team Grid** -- so you can build a Team page in minutes, with drag-and-drop ordering and group taxonomy support.

## Why this is built as WordPress blocks

Divi 5 rebuilt the Visual Builder on top of the WordPress block editor: what Divi 4 called "modules" (registered via the PHP `DiviBuilderModule` / shortcode framework) are, in Divi 5, native WordPress blocks with a React `edit` component and a server-side `render_callback`, surfaced through Divi 5's block inserter and Visual Builder canvas. There is no separate, documented "Divi 5 module SDK" API distinct from `register_block_type()` -- Divi 5 discovers and renders any registered block the same way core Gutenberg does.

So this plugin registers `team-roster-for-divi/team-member-card` and `team-roster-for-divi/team-grid` as standard dynamic blocks:

- They appear in Divi 5's Visual Builder block/module inserter automatically, with Divi's design toolkit (spacing, borders, colors, typography) available via the standard WordPress block `supports` API, which Divi 5 reads the same way core does.
- They also work in plain WordPress (no Divi at all) and inside Divi 4, since Divi 4 pages can still embed WordPress blocks via the block editor.
- No proprietary/undocumented Divi framework classes are used, so the plugin isn't coupled to a specific Divi release.

If Divi isn't detected at all, the blocks still work as plain Gutenberg blocks -- the plugin doesn't hard-block on Divi being active. `trd_get_divi_status()` in `includes/functions.php` is available if you want to build Divi-specific UI on top of this later.

## Features

- **Team Member** custom post type (`trd_team_member`): title (name), featured image (photo), content editor (biography, WYSIWYG with images), plus a meta box for Job Title, Company, Email, Phone and a Link (LinkedIn/website/etc). All fields are REST-exposed (`/wp-json/wp/v2/team-members`) so any block-based/React UI, Divi 5 included, can query them.
- **Group** taxonomy (`trd_team_group`): hierarchical, like Categories (e.g. Leadership, Sales, Engineering). Shown as an admin-list column with a filter dropdown.
- **Drag-and-drop reordering**: drag rows on the Team Members list screen to set manual order, saved via AJAX to the native `menu_order` field, with a save indicator. This order is what the Team Grid uses by default and what the Team Member Card's dropdown is sorted by.
- **Team Member Card** block: pick an existing Team Member from a dropdown (auto-pulling photo/name/title/company/contact/bio), or fill everything in manually for one-off entries that don't need a CPT post. Renders a card with a "Read Bio" button that opens an accessible modal.
- **Team Grid** block: automatically renders every Team Member (or a filtered subset) as a responsive grid of cards. Options: display all vs. selected groups, exclude specific people, sort order (manual/name), responsive column counts (desktop/tablet/mobile), an optional "group sections" mode that renders each group as its own labeled sub-grid, and a few card style overrides (background, text color, corner radius).
- **Bio modal**: vanilla JS (no jQuery dependency), its own scoped CSS (`.trd-modal`), proper `role="dialog"`/`aria-modal`, focus trap, Escape-to-close, click-outside-to-close, and focus restoration to the triggering button on close. Assets are only enqueued on blocks/pages that actually use them (via each block's `viewScript`/`viewStyle`, which WordPress core only loads when the block is present).
- **Getting Started notice**: shown once on Team Member admin screens, dismissible per-user.

## Requirements

- WordPress 6.6+
- PHP 7.4+
- Divi 5 (theme or Divi Builder plugin) recommended for the intended "drag the module onto the page" workflow, but not required -- see above.

## Installation

1. Copy (or clone) this `team-roster-for-divi` folder into `wp-content/plugins/`.
2. Run the JS build (see below) -- the block editor scripts are **not** committed to source control and must be built once.
3. Activate **Team Roster for Divi** from the Plugins screen.

## Build step

The block `edit` UIs are written in JSX and built with `@wordpress/scripts` (the standard WordPress block tooling, wrapping webpack + Babel). This is the same build process any Divi 5 block/module with a React editor UI requires.

```bash
npm install
npm run build
```

This compiles:

- `modules/team-member-card/src/index.js` → `modules/team-member-card/build/index.js` (+ `index.asset.php`)
- `modules/team-grid/src/index.js` → `modules/team-grid/build/index.js` (+ `index.asset.php`)

For active development with rebuild-on-save:

```bash
npm run start
```

CSS is hand-written (no Sass/build step needed for styles): `assets/css/card.css` (card layout, used on both front end and in the editor preview), `assets/css/modal.css` (bio modal, front end only), and each block's `editor.css` (small editor-only chrome).

## Usage

### 1. Add team members

**Team Members → Add New**. Set the title (name), featured image (photo), fill in the *Team Member Details* box (Job Title, Company, Email, Phone, Link), and write the biography in the main content editor.

### 2. Reorder / group

On the **Team Members** list, drag rows to reorder (saves automatically). Use **Team Members → Groups** (or the Group panel on each post) to organize people into groups such as Leadership, Sales, Engineering.

### 3. Add the blocks to a page

Open a page in the Divi 5 Visual Builder (or the block editor) and insert:

- **Team Member Card** -- for one person. In the sidebar, choose "Select an existing Team Member" and pick from the dropdown, or "Enter details manually" to fill in a card without creating a CPT post.
- **Team Grid** -- for the whole team (or a filtered subset) at once. Configure who to show, sort order, columns per breakpoint, optional per-group sections, and card style overrides in the sidebar.

## Plugin structure

```
team-roster-for-divi.php        Main plugin file (headers, bootstrap, hooks)
uninstall.php                   Non-destructive cleanup on plugin deletion
includes/
  functions.php                 Shared helpers (meta keys, Divi detection, card markup, data assembly)
  class-trd-post-type.php       `trd_team_member` CPT registration
  class-trd-taxonomy.php        `trd_team_group` taxonomy registration
  class-trd-meta-boxes.php      Job Title/Company/Email/Phone/Link meta box + sanitization
  class-trd-rest.php            REST-exposes the custom meta fields
  class-trd-admin-list.php      Admin list columns + group filter dropdown
  class-trd-reorder.php         Drag-and-drop AJAX reordering (menu_order)
  class-trd-blocks.php          Registers both blocks, Team Member Card render callback
  class-trd-team-grid-render.php Team Grid query + grouped-sections rendering
  class-trd-activation.php      Activation (flush rewrites) / deactivation
admin/
  class-trd-admin.php           Getting Started notice + its dismissal
  css/admin.css                 Admin list drag styles, notice styles
  js/admin-reorder.js           jQuery UI Sortable + AJAX save
modules/
  team-member-card/
    block.json, editor.css, src/index.js, src/edit.js, build/ (generated)
  team-grid/
    block.json, editor.css, src/index.js, src/edit.js, build/ (generated)
assets/
  css/card.css                  Shared card layout (both blocks, front end + editor)
  css/modal.css                 Shared bio modal styles (front end only)
  js/modal.js                   Shared bio modal behavior (front end only)
languages/                      Text domain: team-roster-for-divi
```

## Internationalization

All user-facing strings use the `team-roster-for-divi` text domain and standard `__()`/`_e()`/`esc_html__()` calls. Generate a `.pot` file with WP-CLI once you have it available:

```bash
wp i18n make-pot . languages/team-roster-for-divi.pot
```

## Coding conventions

- Every function, class, hook, option, meta key and AJAX action is prefixed `trd_`/`Trd_` to avoid collisions.
- All output is escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` for the rich-text bio); all input is sanitized on save (`sanitize_text_field`, `sanitize_email`, `esc_url_raw`) and nonce-checked.
- No external PHP dependencies. The only build-time dependency is `@wordpress/scripts`, used purely to compile the block editor JSX.
