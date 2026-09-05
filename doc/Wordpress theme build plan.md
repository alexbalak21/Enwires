# Enwires — Custom WordPress Theme Build Plan (Option A)

Concrete, file-by-file plan to rebuild the current static site
(`index.php` / `product.php` / `contact.php`) as a custom WordPress theme
called **enwires**, so the team can edit text and add products from
`/wp-admin` with no code changes.

---

## 1. Environment setup

1. Install WordPress (local dev: **LocalWP** or Docker; matches production PHP
   8.1+/MySQL 5.7+).
2. Install and activate plugins, in this order:
   1. **Advanced Custom Fields PRO** — content model + editable fields
   2. **Safe SVG** — lets `enwires-logo.svg` be uploaded through Media Library
   3. **Contact Form 7** (or WPForms Lite) — rebuilds the contact form
   4. **Yoast SEO** — per-page/per-product SEO fields
   5. **UpdraftPlus** — backups, activate from day one
3. Create the theme folder: `wp-content/themes/enwires/`.
4. Delete/ignore the default theme's demo content; set **Settings → Permalinks**
   to "Post name" (needed for clean `/products/siboost/` URLs).

---

## 2. Theme file structure

```
wp-content/themes/enwires/
├── style.css                 # theme header comment + imported design tokens
├── functions.php             # theme setup, enqueues, CPT + ACF registration
├── header.php                # <head>, SEO tags, nav — from includes/header.php
├── footer.php                # site footer — from includes/footer.php
├── front-page.php            # Home (was index.php)
├── page-contact.php          # Contact page template (was contact.php)
├── archive-product.php       # Products grid (new — replaces single product.php)
├── single-product.php        # One product page (was product.php, now dynamic)
├── inc/
│   ├── cpt-product.php       # register_post_type('product', ...)
│   ├── acf-fields.php        # ACF field group definitions (or exported JSON)
│   └── theme-setup.php       # nav menus, logo support, image sizes
├── template-parts/
│   ├── hero.php              # reusable hero block
│   ├── stat.php              # reusable "1.1–4×" stat callout
│   ├── value-list.php        # "What we offer" repeater renderer
│   ├── filmstrip.php         # image filmstrip renderer
│   └── product-card.php      # card used on the Products archive grid
├── assets/
│   ├── css/style.css         # <- copied verbatim from the current site
│   ├── js/main.js            # <- copied verbatim from the current site
│   └── img/                  # only truly static/theme assets (logo fallback);
│                              #   product & content photos move into the
│                              #   WordPress Media Library instead
└── screenshot.png            # theme thumbnail for wp-admin
```

Everything currently in `assets/css/style.css` and `assets/js/main.js` is reused
**unchanged** — no design work is being redone, only how the HTML around it gets
generated.

---

## 3. `functions.php` — what it registers

```php
// Theme support
add_theme_support('title-tag');
add_theme_support('post-thumbnails');   // needed for Product featured images
add_theme_support('custom-logo');
add_theme_support('html5', ['search-form','gallery','caption']);

// Nav menu (replaces the hard-coded $NAV_ITEMS array in config.php)
register_nav_menus(['primary' => 'Primary Navigation']);

// Enqueue the existing stylesheet/script as-is
function enwires_assets() {
    wp_enqueue_style('enwires-style', get_theme_file_uri('assets/css/style.css'), [], '1.0');
    wp_enqueue_script('enwires-main', get_theme_file_uri('assets/js/main.js'), [], '1.0', true);
}
add_action('wp_enqueue_scripts', 'enwires_assets');

// Load the CPT + ACF field registration files
require get_theme_file_path('inc/cpt-product.php');
require get_theme_file_path('inc/acf-fields.php');
require get_theme_file_path('inc/theme-setup.php');
```

---

## 4. Custom Post Type: `Product`

`inc/cpt-product.php`:

```php
function enwires_register_product_cpt() {
    register_post_type('product', [
        'label'        => 'Products',
        'labels'       => [
            'name'          => 'Products',
            'singular_name' => 'Product',
            'add_new_item'  => 'Add New Product',
            'edit_item'     => 'Edit Product',
        ],
        'public'       => true,
        'has_archive'  => true,          // -> /products/
        'rewrite'      => ['slug' => 'products'],
        'menu_icon'    => 'dashicons-analytics',
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest' => true,          // block editor + REST API support
    ]);
}
add_action('init', 'enwires_register_product_cpt');
```

Result in `/wp-admin`: a new **Products** menu item, with **Add New Product**
working exactly like adding a normal Post — title, body text, featured image —
plus the extra fields below.

---

## 5. ACF field groups

Built in **ACF → Field Groups** (UI) and exported to `inc/acf-fields.php` via
ACF's "Export as PHP" so the fields ship with the theme (no manual re-setup on
a new environment).

### 5.1 Field group: "Product Details" (location: Post Type = Product)

| Field label | Field name | Type | Notes |
|---|---|---|---|
| Tagline | `tagline` | Text | One-liner under the title, e.g. "A silicon-doped graphite…" |
| Stat number | `stat_number` | Text | e.g. `1.1–4×` |
| Stat label | `stat_label` | Text | e.g. "energy density vs. standard graphite" |
| Before/after image | `before_after_image` | Image | The bottles comparison photo |
| Before/after caption | `before_after_caption` | Text | |
| How it works | `how_it_works` | Repeater | Sub-fields: `step_title` (Text), `step_text` (Textarea). Team can add/remove/reorder rows. |
| Gallery | `gallery` | Gallery | SEM shots, powder photos — rendered as a filmstrip on the product page |

### 5.2 Field group: "Home Page Content" (location: Page template = Home)

| Field label | Field name | Type |
|---|---|---|
| Hero eyebrow | `hero_eyebrow` | Text |
| Hero headline | `hero_headline` | Text |
| Hero paragraph | `hero_text` | Textarea |
| Hero primary button label / link | `hero_cta_primary_label` / `_link` | Text / Page Link |
| Hero secondary button label / link | `hero_cta_secondary_label` / `_link` | Text / Page Link |
| Who-we-are text | `who_we_are_text` | WYSIWYG |
| Home stat number / label | `home_stat_number` / `home_stat_label` | Text |
| What we offer | `value_props` | Repeater → `title` (Text), `text` (Textarea) |
| Filmstrip images | `filmstrip_images` | Gallery |

### 5.3 Field group: "Contact Page Content" (location: Page template = Contact)

| Field label | Field name | Type |
|---|---|---|
| Heading | `contact_heading` | Text |
| Intro text | `contact_intro` | Textarea |

### 5.4 Options Page: "Theme Settings" (site-wide, via `acf_add_options_page()`)

| Field label | Field name | Type |
|---|---|---|
| Footer location | `site_location` | Text (default "Grenoble, France") |
| Footer email | `site_email` | Text |
| Default SEO image | `default_seo_image` | Image |

This directly replaces the constants currently hard-coded in `includes/config.php`
(`SITE_LOCATION`, `SITE_EMAIL`, `DEFAULT_DESCRIPTION`, etc.) with editable fields.

---

## 6. Templates — mapping old files to new ones

### 6.1 `header.php`
Rebuilt from `includes/header.php`, keeping the exact SEO `<head>` block already
built (description, keywords, canonical, Open Graph, Twitter Card, favicon links),
but:
- `<title>` and meta description/keywords come from **Yoast** instead of
  `$pageTitle` / `$pageDescription` / `$pageKeywords`.
- Favicon (`enwires-logo.svg`) set via **Settings → General → Site Icon** (Safe SVG
  plugin allows the `.svg` upload) instead of a hard-coded `<link rel="icon">`.
- Nav (`Home / Products / Contact`) rendered with `wp_nav_menu(['theme_location' =>
  'primary'])` instead of looping over `$NAV_ITEMS`.
- Logo rendered with `the_custom_logo()` instead of a hard-coded `<img>` tag.

### 6.2 `footer.php`
Rebuilt from `includes/footer.php`: location/email pulled from the Theme Settings
options page (`get_field('site_location', 'option')`), nav reused from the same
`wp_nav_menu()` call, year via `date('Y')` as today.

### 6.3 `front-page.php` (Home)
Rebuilt from `index.php`, section by section, each one pulling from the "Home Page
Content" ACF fields (§5.2) instead of hard-coded copy:
- Hero → `template-parts/hero.php`
- "Who we are" + stat → `template-parts/stat.php`
- "What we offer" → loop over the `value_props` repeater in
  `template-parts/value-list.php`
- Filmstrip → loop over the `filmstrip_images` gallery in
  `template-parts/filmstrip.php`

### 6.4 `archive-product.php` (new — Products grid)
New page, doesn't exist in the current static site. Standard WordPress loop over
all published `product` posts, rendering `template-parts/product-card.php` for
each (image, title, tagline, "View product" link) in the same visual style as the
current value-list/card sections. Becomes the destination of the "Products" nav
item once there's more than one product.

### 6.5 `single-product.php`
Rebuilt from `product.php`, but dynamic:
- Title, intro → post title + post content (`the_title()`, `the_content()`)
- Stat block → `stat_number` / `stat_label` ACF fields
- Before/after section → `before_after_image` / `before_after_caption`
- "How it works" → loop over the `how_it_works` repeater
- Gallery filmstrip → loop over the `gallery` field
- "Bring your own graphite" CTA → kept as static theme markup (or made editable
  later if the team wants to change it per-product)

### 6.6 `page-contact.php`
Rebuilt from `contact.php`: heading/intro from ACF fields (§5.3), the hand-rolled
PHP `$_POST` validation/handling logic is **replaced entirely** by the Contact
Form 7 (or WPForms) shortcode, configured with the same required fields already
designed (name*, email*, phone, subject, message*) and the same target address
(`contact@enwires.com`, pulled from Theme Settings).

---

## 7. Step-by-step execution order

1. Environment + plugins installed (§1).
2. `style.css` theme header + `functions.php` skeleton, confirm theme activates
   with a blank white screen (no fatal errors) before building further.
3. Enqueue existing `assets/css/style.css` + `assets/js/main.js`; confirm fonts/
   colors load on a default WordPress page.
4. Build `header.php` + `footer.php`; confirm nav menu + logo + footer render on
   every default WP page.
5. Register the `Product` CPT (§4); confirm **Products** appears in `/wp-admin`.
6. Build all ACF field groups (§5) via the ACF UI; export to `inc/acf-fields.php`.
7. Build `single-product.php` + `archive-product.php`; create the real **SiBoost**
   product entry with the existing copy/photos to test against.
8. Build `front-page.php` + the Home ACF fields; create the Home page, set it as
   the site's static front page (**Settings → Reading**), fill in existing copy.
9. Build `page-contact.php`; install/configure the contact form plugin; create the
   Contact page.
10. Build the Theme Settings options page; wire the footer/SEO defaults to it.
11. Full QA pass (§8).
12. Content freeze → migrate final copy/images → SEO fields via Yoast → go live
    (DNS cutover + redirects), per the earlier conversion plan's §6–7.

---

## 8. QA checklist before go-live

- [ ] Home, Products archive, single Product, and Contact all match the current
      design pixel-for-pixel (spacing, colors, type, hero image crop).
- [ ] Adding a second test Product from `/wp-admin` (no code) renders correctly on
      both the archive grid and its own page.
- [ ] "How it works" and "What we offer" repeaters: adding/removing/reordering
      rows in `/wp-admin` updates the front end correctly.
- [ ] Contact form: required-field and email-format validation both still work;
      a real test submission arrives at `contact@enwires.com`.
- [ ] Favicon (SVG) displays correctly across browsers, PNG fallback on Safari.
- [ ] Per-page/per-product SEO title, description, canonical URL, and Open Graph
      image all render correctly (compare against the site's existing SEO plan).
- [ ] Mobile nav toggle, responsive breakpoints, and keyboard focus states all
      still work as in the current static site.
- [ ] Page load speed checked (Lighthouse/PageSpeed) — should stay close to the
      current static site once caching (WP Rocket/W3TC) is enabled.

---

## 9. Result

Once this is live, the team's day-to-day workflow is:

- **Edit text anywhere** → open the relevant Page or Product in `/wp-admin`, edit
  the field, click Update.
- **Add a product** → *Products → Add New*, fill in title/photo/stat/how-it-works,
  Publish — it appears on the Products grid automatically, no developer involved.
- **Change footer email/location, logo, or SEO defaults** → one central Theme
  Settings screen.

Anything beyond that (new section types, new page templates, new custom post
types, design changes) still needs a developer, as noted in the original
conversion plan.