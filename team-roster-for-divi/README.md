# Team Roster for Divi

Adds a **Team Member** post type plus `[team_member_card]` and `[team_grid]` shortcodes, so you can build a Team page with drag-and-drop ordering and group taxonomy support -- and drop the result into **Divi 4 or Divi 5** identically.

## Why shortcodes instead of a Visual Builder module

Divi 4's Visual Builder and Divi 5's are built on genuinely different foundations: Divi 4 modules are PHP classes emitting shortcodes (`ET_Builder_Module`), while Divi 5 rebuilt the builder on the WordPress block editor, so a "Divi 5 module" is, under the hood, a React block with a server render callback. Targeting either one directly means picking a side -- a block registered for Divi 5 doesn't exist as a Divi 4 module, and vice versa, and Divi 5's block-registration details are still moving as the framework matures.

A WordPress shortcode sidesteps that entirely: both Divi 4 and Divi 5 ship a **Text** module (and Divi 4 also has a dedicated **Code** module) that runs `do_shortcode()` on its content, so `[team_grid]` renders identically in either builder, in the classic editor, in a widget, or in a shortcode block -- with zero Divi-version detection and nothing that can drift out of sync with a future Divi release. It also means **no JS build step at all**: the shortcode output is plain server-rendered HTML plus a small vanilla-JS modal script.

## Features

- **Team Member** custom post type (`trd_team_member`): title (name), featured image (photo), content editor (biography, WYSIWYG with images), plus a meta box for Job Title, Company, Email, Phone and a Link (LinkedIn/website/etc). All fields are REST-exposed (`/wp-json/wp/v2/team-members`) for any other tooling that wants to query them.
- **Group** taxonomy (`trd_team_group`): hierarchical, like Categories (e.g. Leadership, Sales, Engineering). Shown as an admin-list column with a filter dropdown.
- **Drag-and-drop reordering**: drag rows on the Team Members list screen to set manual order, saved via AJAX to the native `menu_order` field, with a save indicator. This order is what `[team_grid]` uses by default.
- **`[team_member_card]`** shortcode: reference an existing Team Member by ID (auto-pulling photo/name/title/company/contact/bio), or pass every field manually as attributes for a one-off card that doesn't need a CPT post. Renders a card with a "Read Bio" button that opens an accessible modal. Each Team Member's edit screen has a ready-to-copy `[team_member_card id="…"]` box in the sidebar.
- **`[team_grid]`** shortcode: automatically renders every Team Member (or a filtered subset) as a responsive grid of cards. Attributes control: all vs. selected groups, excluded people, sort order (manual/name), responsive column counts, an optional "group sections" mode that renders each group as its own labeled sub-grid, and a few card style overrides (background, text color, corner radius).
- **Bio modal**: vanilla JS (no jQuery dependency), its own scoped CSS (`.trd-modal`), proper `role="dialog"`/`aria-modal`, focus trap, Escape-to-close, click-outside-to-close, and focus restoration to the triggering button on close. Assets are only enqueued when a shortcode actually renders on the current request.
- **Display Settings** (Team Members → Display Settings): site-wide defaults for the Team Grid's column counts (desktop/tablet/mobile, still overridable per-shortcode) and typography -- font family, size, weight and justification -- for the name heading, group section headings, job title/company line, contact info, and the biography text, each controlled independently.
- **Getting Started notice**: shown once on Team Member admin screens, dismissible per-user, plus a permanent one-line `[team_grid]` reminder above the Team Members list.

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Divi 4 or Divi 5 (theme or Divi Builder plugin) for the intended workflow, but neither is required -- the shortcodes work in any WordPress theme/page builder that renders shortcodes.

## Installation

1. Copy (or clone) this `team-roster-for-divi` folder into `wp-content/plugins/`.
2. Activate **Team Roster for Divi** from the Plugins screen. That's it -- there is no build step.

## Usage

### 1. Add team members

**Team Members → Add New**. Set the title (name), featured image (photo), fill in the *Team Member Details* box (Job Title, Company, Email, Phone, Link), and write the biography in the main content editor. The sidebar's *Shortcode* box shows `[team_member_card id="…"]` for that person, ready to copy.

### 2. Reorder / group

On the **Team Members** list, drag rows to reorder (saves automatically). Use **Team Members → Groups** (or the Group panel on each post) to organize people into groups such as Leadership, Sales, Engineering.

### 3. Set the default look (optional)

**Team Members → Display Settings** controls, sitewide:

- The Team Grid's default column count for desktop, tablet (≤ 980px) and mobile (≤ 767px) -- a `[team_grid]` shortcode can still override these per-instance with its own `columns` / `columns_tablet` / `columns_mobile` attributes.
- Font family, size, weight and justification (left/center/right/justify), set independently for: the name heading, group section headings, the job title & company line, contact info, and the biography text shown in the Read Bio modal. Leave any field blank/"Theme default" to inherit your theme's normal styling.

### 4. Add the shortcodes to a page

**In Divi 4:** add a **Text** module (or **Code** module) to your layout and paste the shortcode into its content field.

**In Divi 5:** add a **Text** block/module and paste the shortcode the same way.

**Show the whole team:**

```
[team_grid]
```

**Show only specific groups, each as its own labeled section:**

```
[team_grid mode="groups" groups="leadership,engineering" sections="yes"]
```

**Show everyone except a couple of people, sorted alphabetically, 4 columns wide:**

```
[team_grid exclude="12,45" sort="title_asc" columns="4" columns_tablet="2" columns_mobile="1"]
```

**Show one person by their Team Member post ID** (copy this from that person's edit screen):

```
[team_member_card id="123"]
```

**Show one person without creating a CPT post**, filling everything in manually (the content between the tags becomes their bio):

```
[team_member_card name="Jane Doe" photo="https://example.com/jane.jpg" job_title="CEO" company="Acme Inc" email="jane@acme.com" phone="555-1234" link="https://linkedin.com/in/janedoe"]
Jane co-founded Acme in 2014 and has led product strategy ever since...
[/team_member_card]
```

### `[team_grid]` attributes

| Attribute | Values | Default | Description |
|---|---|---|---|
| `mode` | `all`, `groups` | `all` | Show every Team Member, or only the groups listed in `groups`. |
| `groups` | comma-separated group slugs | *(empty)* | Which groups to include when `mode="groups"`. |
| `exclude` | comma-separated Team Member post IDs | *(empty)* | Specific people to leave out regardless of group. |
| `sort` | `menu_order`, `title_asc`, `title_desc` | `menu_order` | `menu_order` respects the drag-and-drop order set in the admin list. |
| `sections` | `yes`, `no` | `no` | Render each group as its own heading + sub-grid instead of one flat grid. |
| `columns` / `columns_tablet` / `columns_mobile` | `1`-`6` | `3` / `2` / `1` | Responsive column counts (breakpoints match Divi's: 980px, 767px). |
| `button_label` | text | `Read Bio` | Label on each card's bio button. |
| `card_bg` / `card_color` | any CSS color | *(theme default)* | Card background / text color override. |
| `card_radius` | number (px) | `8` | Card corner radius override. |

### `[team_member_card]` attributes

| Attribute | Description |
|---|---|
| `id` | Existing Team Member post ID. When set, every other attribute except `button_label` is ignored -- the CPT post's own fields are used. |
| `name`, `photo`, `job_title`, `company`, `email`, `phone`, `link` | Manual-mode fields, used when `id` is omitted. `photo` is a direct image URL. `name` is required for a card to render. |
| Enclosed content | Manual-mode biography (basic HTML allowed). Ignored when `id` is set. |
| `button_label` | Label on the bio button. |

## Plugin structure

```
team-roster-for-divi.php        Main plugin file (headers, bootstrap, hooks)
uninstall.php                   Non-destructive cleanup on plugin deletion
includes/
  functions.php                 Shared helpers (meta keys, Divi detection, card markup, data assembly)
  class-trd-post-type.php       `trd_team_member` CPT registration
  class-trd-taxonomy.php        `trd_team_group` taxonomy registration
  class-trd-meta-boxes.php      Job Title/Company/Email/Phone/Link meta box + the per-post shortcode box
  class-trd-rest.php            REST-exposes the custom meta fields
  class-trd-admin-list.php      Admin list columns + group filter dropdown
  class-trd-reorder.php         Drag-and-drop AJAX reordering (menu_order)
  class-trd-shortcodes.php      [team_member_card] and [team_grid] shortcode handlers
  class-trd-team-grid-render.php Team Grid query + grouped-sections rendering
  class-trd-activation.php      Activation (flush rewrites) / deactivation
admin/
  class-trd-admin.php           Getting Started notice, shortcode reminder, notice dismissal
  class-trd-settings.php        Display Settings page: grid columns + per-field typography, generates the inline CSS
  css/admin.css                 Admin list drag styles, notice styles
  js/admin-reorder.js           jQuery UI Sortable + AJAX save
assets/
  css/card.css                  Card layout (shared by both shortcodes)
  css/modal.css                 Bio modal styles
  js/modal.js                   Bio modal behavior (vanilla JS, no jQuery)
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
- No build step and no external PHP or JS dependencies.
