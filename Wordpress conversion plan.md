# Enwires — WordPress Conversion Plan

## 0. Goal

Convert the current static PHP site (`index.php`, `product.php`, `contact.php`) into a
WordPress site where the Enwires team, with no coding knowledge, can:

1. Edit any text on any page themselves.
2. Add, edit, and remove **products** (SiBoost today, more later) without a developer.
3. Keep the current look (colors, type, layout, SEM photography, logo) exactly as designed.

This plan covers two ways to get there, a recommendation, the concrete build steps,
the content model, hosting, and a rollout checklist.

---

## 1. Two possible approaches

### Option A — Custom WordPress theme (recommended)

Rebuild the existing HTML/CSS as a proper WordPress theme: the same markup and
`style.css` we already have, but with the PHP hard-coded text replaced by WordPress
template tags that pull from the admin (via **Advanced Custom Fields**, see §3) and a
custom **Products** post type for SiBoost-style entries.

**Pros**
- Pixel-identical to what's already built and approved — no redesign risk.
- Fast page loads (no heavy page-builder JS/CSS), keeps the current fast, clean feel.
- Full control over exactly which pieces of text/image are editable, so the team can't
  accidentally break the layout.
- Products become a real WordPress content type: filterable, sortable, reusable in
  future (e.g. a "Products" grid page appears automatically as products are added).

**Cons**
- Needs a developer for the initial theme build (this plan is exactly that spec).
- Structural changes (e.g. adding a whole new section type) still need a developer
  later — though everyday text/image/product edits do not.

### Option B — Page builder (Elementor / Bricks) on a generic theme

Rebuild the pages visually using a drag-and-drop builder on top of a blank theme.

**Pros**
- The team can move sections around and build new page layouts themselves, not just
  edit text.
- No developer needed for future new *pages* (only for new *page types*/logic).

**Cons**
- Hard to reproduce the current design exactly (diagonal ribbon divider, filmstrip,
  stat callouts) without heavy custom CSS anyway — much of Option A's dev work still
  happens, just inside the builder instead of a theme.
- Heavier pages (more JS/CSS), slower load times, worse Core Web Vitals/SEO than a
  custom theme.
- Products still need either the builder's own e-commerce/CPT add-on or a plugin like
  ACF — so the "no plugin" simplicity is often an illusion in practice.
- Easier for a well-meaning edit to visually break the design (spacing, colors) since
  the team has full layout freedom, not just text fields.

### Recommendation

**Option A.** The site's design is already finished and specific (diagonal ribbon
motif, stat callouts, filmstrip, before/after comparison) — rebuilding it as a page
builder template gets you the same amount of custom CSS work as a theme, but with a
less predictable editing experience for the team afterward. A custom theme with
clearly labelled ACF fields gives the team **exactly** the fields they need to edit
(headline, paragraph, stat number, product photo, product spec sheet...) without
being able to accidentally wreck the layout.

The rest of this plan assumes **Option A**.

---

## 2. What "editable" means, page by page

| Page | Editable via WordPress admin |
|---|---|
| **Home** | Hero eyebrow, hero headline, hero paragraph, hero CTA button labels/links, "Who we are" text, the "1.1–4×" stat number + label, all 3 "What we offer" items (title + text each, add/remove/reorder), the 5 filmstrip photos + captions |
| **Products** (was `product.php`, now a listing + one page per product) | Each product: name, tagline, hero description, key stat (e.g. "1.1–4×") + stat label, "What is it" text, before/after photo + caption, "How it works" steps (add/remove/reorder), gallery images, SEO fields. New products are added the same way, automatically appearing in the Products menu/grid. |
| **Contact** | Heading, intro text, location, email, phone (optional), the contact form fields (via plugin settings, see §4) |
| **Site-wide** | Logo, tagline, footer text, primary color/nav labels (theme settings), SEO title/description per page (via Yoast/RankMath, see §4) |

---

## 3. Content model (what we build in WordPress)

### 3.1 Pages (built-in WordPress "Pages")
- **Home** — a Page using a custom "Home" template, with ACF fields for every hero/
  section piece of text described above.
- **Contact** — a Page using a custom "Contact" template, with ACF fields for the
  intro text + a shortcode/block for the form (see §4).

### 3.2 Custom Post Type: `Product`
This is the key piece that lets the team **add new products** without a developer.

- Post type slug: `product`, plural label "Products", shown in the WP admin sidebar
  with its own icon.
- Each Product has:
  - **Title** (e.g. "SiBoost") — standard WordPress field.
  - **Featured image** — standard WordPress field, used as the product's main photo
    (e.g. the SEM particle shot).
  - **Content** (standard WordPress editor) — for the free-form "What is it" copy.
  - **ACF field group "Product details"**, attached only to the `product` post type:
    - `tagline` (text) — one-line description under the title.
    - `stat_number` (text) — e.g. "1.1–4×".
    - `stat_label` (text) — e.g. "energy density vs. standard graphite".
    - `before_after_image` (image) — the comparison photo.
    - `before_after_caption` (text).
    - `how_it_works` (repeater field) — each row = one step, with `step_title` and
      `step_text`. The team can add, remove, and reorder steps freely.
    - `gallery` (gallery field) — extra product photos (SEM shots, powder, etc.),
      reused for a filmstrip-style block on the product page.
- A **Products archive page** (`/products/`) is added automatically by WordPress,
  themed to show a grid of product cards (name, featured image, tagline) linking to
  each product's own page — this becomes the new "Product" nav item once there's more
  than one product.
- Individual product URLs: `/products/siboost/`, `/products/[next-product]/`, etc.

### 3.3 Site-wide options
- **ACF Options Page** ("Theme Settings") for things that appear on every page:
  footer email/location, social links (if added later), default SEO image.
- **WordPress Customizer** for logo upload and site tagline (native WP features, no
  plugin needed).

---

## 4. Plugins

Kept deliberately minimal — every plugin here has a clear, specific job:

| Plugin | Why |
|---|---|
| **Advanced Custom Fields PRO** | Powers every editable field described in §3 (repeaters, image fields, options page). This is the plugin that makes "edit text / add products" possible without touching code. |
| **Contact Form 7** *or* **WPForms Lite** | Rebuilds the existing name/phone/email/subject/message form as a WordPress form with the same validation rules already built (required name, valid email, required message), emailing submissions to `contact@enwires.com`. |
| **Yoast SEO** *or* **RankMath** | Per-page/per-product SEO title, meta description, and Open Graph image — replacing the `$pageTitle` / `$pageDescription` / `$pageKeywords` variables already used in the current site's `header.php`, so the SEO work already done isn't lost. |
| **Safe SVG** | Allows uploading `.svg` files (like `enwires-logo.svg`) through the normal WordPress Media Library, which is blocked by default for security reasons. |
| **WP Rocket** *or* **W3 Total Cache** (optional) | Page caching, to keep load times as fast as the current static PHP site now that pages are database-driven. |
| **UpdraftPlus** | Automated backups (files + database) — essential once the team is editing content directly, so any mistake can be rolled back. |

No page builder, no all-in-one "mega plugin", no unused e-commerce plugin (Enwires
isn't taking payments online — if that changes later, WooCommerce can be added on top
of the same `Product` post type).

---

## 5. Theme build plan (developer work)

1. **Scaffold a custom theme** (`enwires` theme folder) with `style.css` header,
   `functions.php`, `screenshot.png`.
2. **Port the existing design system as-is**: copy `assets/css/style.css` and
   `assets/js/main.js` into the theme, enqueue them properly via
   `wp_enqueue_style()` / `wp_enqueue_script()` in `functions.php` (no more raw
   `<link>`/`<script>` tags).
3. **Convert shared markup**:
   - `includes/header.php` → theme's `header.php`, using `wp_head()`, dynamic
     `<title>`/meta via Yoast, and the WordPress menu system (`wp_nav_menu()`) for
     Home / Products / Contact instead of the hard-coded `$NAV_ITEMS` array.
   - `includes/footer.php` → theme's `footer.php`, using `wp_footer()`, with the
     email/location pulled from the ACF Options Page instead of `config.php`
     constants.
4. **Register the `Product` custom post type and its ACF field group** (§3.2), plus
   the Products archive template (`archive-product.php`) and single product template
   (`single-product.php`).
5. **Build the Home page template** (`page-home.php` or a Home-specific block
   template) reproducing the hero, "who we are," stat, value-prop list, and filmstrip
   sections — each piece of text/image pulled from ACF fields instead of hard-coded
   HTML.
6. **Build the Contact page template**, replacing the hand-rolled PHP form-handling
   logic in `contact.php` with the chosen form plugin's shortcode/block, configured
   with the same required fields and validation already designed.
7. **Register the logo/menus/theme supports** in `functions.php`
   (`add_theme_support('custom-logo')`, `register_nav_menus()`, featured images,
   etc.) so the team can manage these from *Appearance → Customize* and
   *Appearance → Menus*.
8. **Re-implement the favicon** using the already-produced `enwires-logo.svg` via
   `add_theme_support('site-icon')`-compatible markup, or the native *Settings → General*
   Site Icon uploader (SVG via the Safe SVG plugin).
9. **QA pass**: compare every page side-by-side against the current static site
   (spacing, colors, responsive behavior, form validation, favicon, SEO tags) before
   go-live.

---

## 6. Migration steps (content)

1. Set up WordPress on the chosen host (see §7) with the theme from §5 installed and
   activated, plugins from §4 installed and configured.
2. Create the **Home** and **Contact** pages, fill in their ACF fields with the exact
   copy already written for the current site (all of it is documented in the current
   `index.php` / `contact.php` files, so this is copy-paste, not rewriting).
3. Create the **SiBoost** product entry, filling in:
   - Title: SiBoost
   - Tagline, stat ("1.1–4×"), stat label
   - Featured image + before/after image + gallery from `assets/img/`
     (`bottles.jpg`, `graphite-particle.jpg`, `graphite-pouder.jpg`,
     `graphite-pouder-purifued.jpg`, `micorscorpe-zoom.jpg`, `microscope-zoom-2.jpg`)
   - "How it works" repeater: the 3 steps already written (start from standard
     graphite / introduce silicon / verify under electron microscopy)
4. Upload the logo (`logo.png` and `enwires-logo.svg`) via the Customizer and Site
   Icon settings.
5. Set the SEO title/description/keywords per page and per product using Yoast/
   RankMath, matching the values already defined in the current site's
   `$pageDescription` / `$pageKeywords` variables (documented in the earlier SEO
   work).
6. Point the domain's DNS at the new WordPress hosting, or move WordPress into the
   existing hosting account, and set up an SSL certificate.
7. Set up 301 redirects if any URLs change (e.g. `product.php` → `/products/siboost/`)
   so any existing links/search rankings aren't lost.

---

## 7. Hosting & requirements

- **PHP 8.1+**, **MySQL 5.7+/MariaDB 10.3+** (any standard WordPress host qualifies —
  e.g. O2switch, Infomaniak, or a managed WP host like WP Engine/Kinsta if the team
  wants managed backups/updates included).
- **SSL certificate** (usually free via Let's Encrypt, provided by most hosts).
- Recommended: staging environment (many hosts include one) so future theme/plugin
  updates can be tested before going live.

---

## 8. What the team will be able to do afterward (day-to-day)

- Log into `/wp-admin`, click **Products → Add New** to add a new product: fill in
  name, photos, the stat number, and the "how it works" steps — no code, no
  developer.
- Edit any existing product or page text by clicking into the field and typing —
  same WordPress editing experience used by millions of sites.
- Reorder or remove "What we offer" items and "How it works" steps by dragging rows
  in the repeater field.
- Update the footer email/location/logo from one central settings screen.
- See SEO title/description fields directly under each page/product editor (Yoast/
  RankMath), with a live preview of how it'll look in Google search results.

## 9. What still needs a developer afterward

- Structural changes: a brand-new section type, a new page template, a new custom
  post type (e.g. "Case studies," "Team members").
- Design changes: new colors/fonts/layout beyond what's exposed as an editable field.
- Plugin/theme major-version upgrades and security patching (or hand this off to a
  managed WordPress host / maintenance retainer).

---

## 10. Suggested timeline

| Phase | Work | Estimate |
|---|---|---|
| 1 | Theme scaffold, header/footer port, design system port | 1–2 days |
| 2 | Custom Post Type + ACF fields for Products, single/archive templates | 1–2 days |
| 3 | Home + Contact page templates, form plugin setup | 1–2 days |
| 4 | Content migration (copy, images, SEO fields), QA pass, favicon | 1 day |
| 5 | Hosting setup, DNS cutover, redirects, go-live | 0.5–1 day |

**Total: roughly 5–8 working days** for a developer, after which all day-to-day
text and product edits belong to the Enwires team.