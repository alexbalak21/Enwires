# Converting the Enwires site to WordPress

This is a step-by-step procedure for converting the current site — plain PHP,
4 languages (EN/FR/ZH/JA), 3 pages (Home / Product / Contact) — into a
WordPress site your client can edit themselves: text, images, and adding new
products, in every language, from `/wp-admin`.

It reflects the site **as it currently exists**, including the multilingual
system (`includes/lang/*.php`, `t()`, `/fr/` `/zh/` `/ja/` folders,
`includes/language-detect.php`, `set-lang.php`) and the photo galleries added
later (pilot-scale production, powder-to-particle, coin-cell assembly).

---

## 0. The short version

- **Theme:** a custom WordPress theme, reusing the existing `assets/css/style.css`
  and `assets/js/main.js` untouched.
- **Content editing:** Advanced Custom Fields (ACF) PRO — every editable text
  block and image becomes a field in `/wp-admin`.
- **Products:** a `Product` custom post type, so a second product can be added
  later without a developer.
- **Multilingual:** **Polylang** (free) replaces almost all of the custom i18n
  code (`includes/i18n.php`, `language-detect.php`, `set-lang.php`, the manual
  `hreflang` loop) with plugin functionality. This is a genuine simplification,
  not just a like-for-like port — see §5.
- **The translations already exist.** Every string in
  `includes/lang/{en,fr,zh,ja}.php` is the finished, human-translated copy —
  migrating content is mostly copy-pasting those values into WordPress
  fields, not re-translating anything.

---

## 1. Environment setup

1. Install WordPress locally (LocalWP or Docker), PHP 8.1+, MySQL 5.7+/MariaDB
   10.3+ — matching what the current site already assumes.
2. Install and activate plugins, in this order:
   1. **Advanced Custom Fields PRO** — the content model (§3)
   2. **Polylang** (free) — multilingual pages, posts, menus, and the
      language switcher (§5)
   3. **Safe SVG** — lets `enwires-logo.svg` / `enwires-logo-currentColor.svg`
      upload through the normal Media Library
   4. **Contact Form 7** (or **WPForms Lite**, which has first-class Polylang
      support) — rebuilds the contact form
   5. **Yoast SEO** (or **RankMath**) — per-page/per-language SEO fields;
      both integrate with Polylang for automatic `hreflang` tags
   6. **WP Rocket** or **W3 Total Cache** (optional) — page caching
   7. **UpdraftPlus** — backups, turn on from day one
3. Create the theme folder: `wp-content/themes/enwires/`.
4. Set **Settings → Permalinks** to "Post name" (needed for `/products/siboost/`
   and for Polylang's `/fr/`, `/zh/`, `/ja/` URL prefixes).

---

## 2. Theme file structure

```
wp-content/themes/enwires/
├── style.css                 # theme header + @import of the real stylesheet
├── functions.php             # theme setup, enqueues, CPT + ACF registration
├── header.php                # <head>, SEO tags, nav — from includes/header.php
├── footer.php                # site footer — from includes/footer.php
├── front-page.php            # Home (was index.php)
├── page-contact.php          # Contact page template (was contact.php)
├── archive-product.php       # Products grid (new — for when a 2nd product exists)
├── single-product.php        # One product page (was product.php, now dynamic)
├── inc/
│   ├── cpt-product.php       # register_post_type('product', ...)
│   ├── acf-fields.php        # ACF field group definitions (exported PHP)
│   └── theme-setup.php       # nav menus, logo support, image sizes
├── template-parts/
│   ├── hero.php
│   ├── stat.php               # the "1.1–4×" callout
│   ├── value-list.php         # "What we offer" repeater renderer
│   ├── gallery-strip.php      # reusable image-strip renderer (see below)
│   └── product-card.php       # card for the Products archive grid
├── assets/
│   ├── css/style.css          # <- copied verbatim from the current site
│   ├── js/main.js             # <- copied verbatim from the current site
│   └── img/                   # logo + favicon only; content photos move
│                               #   into the WordPress Media Library
└── screenshot.png
```

`assets/css/style.css` and `assets/js/main.js` are reused **unchanged** — the
design work (the reveal animations, the ribbon divider, the filmstrip
component, the language-switcher styling) is already done and doesn't need
to be redone.

**One template part worth calling out:** `template-parts/gallery-strip.php`.
The current site has three separate image-strip sections built from the same
CSS component (`.filmstrip`): "From powder to particle" (6 images), "Built at
pilot scale" (3 images), and "From material to cell" (4 images). In WordPress
these all become the same reusable template part, fed by a different ACF
Gallery field each time — one component, three uses, matching how the CSS
already works.

---

## 3. Content model (ACF field groups)

Every field below corresponds to a specific place in the current code —
either a literal string in `includes/lang/en.php` (translated into the other
three files), or an `<img>` tag in `index.php` / `product.php`.

### 3.1 Field group: "Home Page Content" (location: Page = Home)

| Field label | Field name | Type | Source (current site) |
|---|---|---|---|
| Hero eyebrow | `hero_eyebrow` | Text | `home.hero.eyebrow` |
| Hero headline | `hero_headline` | Text | `home.hero.headline` |
| Hero text | `hero_text` | Textarea | `home.hero.text` |
| Hero primary button label | `hero_cta_primary_label` | Text | `home.hero.cta_primary` |
| Hero secondary button label | `hero_cta_secondary_label` | Text | `home.hero.cta_secondary` |
| Who-we-are heading | `who_heading` | Text | `home.who.heading` |
| Who-we-are text | `who_text` | WYSIWYG | `home.who.p1` + `home.who.p2` |
| Stat number | `who_stat_number` | Text | `home.who.stat_number` (e.g. `1.1–4×`) |
| Stat label | `who_stat_label` | Text | `home.who.stat_label` |
| What-we-offer heading | `offer_heading` | Text | `home.offer.heading` |
| What-we-offer items | `offer_items` | Repeater → `title` (Text), `text` (Textarea) | `home.offer.item1/2/3.*` |
| Pilot section heading | `pilot_heading` | Text | `home.pilot.heading` |
| Pilot section text | `pilot_text` | Textarea | `home.pilot.text` |
| Pilot gallery | `pilot_gallery` | Gallery | `pilot-reactor-technician.jpg`, `pilot-reactor-technician-zoom-on-hands.jpg`, `putting-graphite-powder-macro.jpg` |
| Powder section heading | `powder_heading` | Text | `home.filmstrip.heading` |
| Powder section text | `powder_text` | Textarea | `home.filmstrip.text` |
| Powder gallery | `powder_gallery` | Gallery | the 6 images currently in the "From powder to particle" filmstrip |

Note on image **alt text**: don't put alt text in a separate ACF field. WordPress's
Gallery/Image fields already store alt text on the attachment itself (set it once
in the Media Library, per language — Polylang can sync or translate media,
see §5). This replaces the current `home.filmstrip.alt1` … `alt6` string keys.

### 3.2 Custom Post Type: `Product` (for SiBoost, and any future product)

- Post type slug: `product`, plural label "Products".
- Standard WordPress fields used: **Title** (`SiBoost`), **Featured image**,
  **Content** (the free-form "What is SiBoost?" copy — `product.what.p1` +
  `product.what.p2`).
- ACF field group "Product Details" (location: Post Type = Product):

| Field label | Field name | Type | Source |
|---|---|---|---|
| Hero text | `hero_text` | Textarea | `product.hero.text` |
| Stat number | `stat_number` | Text | `product.stat.number` |
| Stat label | `stat_label` | Text | `product.stat.label` |
| Before/after image | `before_after_image` | Image | `bottles.jpg` |
| Before/after caption | `before_after_caption` | Text | `product.compare.caption` |
| How it works | `how_it_works` | Repeater → `step_title` (Text), `step_text` (Textarea) | `product.how.step1/2/3.*` |
| How-it-works images | `how_it_works_images` | Gallery | `graphite-particle.jpg`, `electrode-coating-compare.jpg` |
| "From material to cell" heading | `cells_heading` | Text | `product.cells.heading` |
| "From material to cell" text | `cells_text` | Textarea | `product.cells.text` |
| "From material to cell" gallery | `cells_gallery` | Gallery | the 4 coin-cell assembly photos |
| Closing CTA heading | `cta_heading` | Text | `product.cta.heading` |
| Closing CTA text | `cta_text` | Textarea | `product.cta.text` |
| Closing CTA button label | `cta_button_label` | Text | `product.cta.button` |

This is the field group that matters most: once it exists, adding a second
product is *Products → Add New*, filling in these same fields, no developer
involved — the entire point of this migration.

### 3.3 Field group: "Contact Page Content" (location: Page = Contact)

| Field label | Field name | Type | Source |
|---|---|---|---|
| Heading | `contact_heading` | Text | `contact.hero.title` |
| Intro text | `contact_intro` | Textarea | `contact.hero.text` |

The form fields themselves (name/phone/email/subject/message, the "*
required" note, and the validation/success messages) are **not** ACF fields —
they're configured once inside the Contact Form 7 / WPForms form builder
(§6), which has its own multilingual handling.

### 3.4 Options Page: "Theme Settings" (site-wide, `acf_add_options_page()`)

| Field label | Field name | Type | Source |
|---|---|---|---|
| Footer location | `site_location` | Text | `SITE_LOCATION` in `includes/config.php` ("Grenoble, France" — kept identical in every language, not translated, for postal-address reasons) |
| Footer email | `site_email` | Text | `SITE_EMAIL` |
| Default SEO image | `default_seo_image` | Image | `assets/img/bottles.jpg` (current `og:image` fallback) |

---

## 4. Templates — mapping old files to new ones

### 4.1 `header.php`
Rebuilt from `includes/header.php`. Most of its current logic is **replaced by
Polylang + Yoast**, not manually re-implemented:

| Currently in `includes/header.php` | In WordPress |
|---|---|
| `$metaTitle` / `$metaDescription` / `$metaKeywords` | Yoast's title/meta fields |
| Manual `hreflang` loop over `$GLOBALS['LANGUAGES']` | Automatic — Yoast + Polylang generate this |
| `$canonicalUrl` built from `SITE_URL` + `PAGE_BASE` | Yoast's canonical, automatic |
| `og:locale` per language | Automatic via Yoast + Polylang |
| `$NAV_ITEMS` loop + `PAGE_BASE` prefixing | `wp_nav_menu()`, one menu per language via Polylang |
| The `<span class="lang-switch">` block (§ current header.php) | Polylang's language-switcher widget/block, styled with the **same** `.lang-switch` CSS already in `style.css` — just change the markup Polylang outputs to reuse those class names, or wrap it once in a small template part |
| Favicon `<link>` tags | Unchanged — same markup, same files |

### 4.2 `footer.php`
Rebuilt from `includes/footer.php`: location/email pulled from the Theme
Settings options page, nav from `wp_nav_menu()`, year via `date('Y')`.

### 4.3 `front-page.php` (Home)
Rebuilt from `index.php`, each section pulling from the "Home Page Content"
ACF fields (§3.1) instead of `t('home.…')` calls:
- Hero → `template-parts/hero.php`
- "Who we are" + stat → inline + `template-parts/stat.php`
- "What we offer" → loop over `offer_items` repeater
- "Built at pilot scale" → heading/text + `template-parts/gallery-strip.php`
  fed by `pilot_gallery`
- "From powder to particle" → heading/text + `gallery-strip.php` fed by
  `powder_gallery`

### 4.4 `archive-product.php` / `single-product.php`
`single-product.php` is rebuilt from `product.php`: title/content from the
post itself, stat block from `stat_number`/`stat_label`, before/after from
`before_after_image`/`before_after_caption`, "How it works" from the
`how_it_works` repeater + `how_it_works_images`, "From material to cell" from
`cells_heading`/`cells_text`/`cells_gallery`, closing CTA from the `cta_*`
fields. `archive-product.php` is new (doesn't exist in the current site) — a
simple grid of `product-card.php`, becomes relevant once a second product
exists.

### 4.5 `page-contact.php`
Rebuilt from `contact.php`: heading/intro from ACF (§3.3); the hand-rolled
PHP `$_POST` validation/handling (`contact.form.error_*` / `success` strings)
is replaced entirely by the Contact Form 7 / WPForms shortcode, with the same
required fields (name*, email*, phone, subject, message*) and the same target
address.

---

## 5. Multilingual: Polylang setup

This is the biggest structural difference from converting a single-language
site, and it's worth doing carefully — but it also **removes** a meaningful
amount of custom code, which is worth explaining to whoever inherits this
site next.

### 5.1 What Polylang replaces

| Custom file today | Replaced by |
|---|---|
| `includes/i18n.php` (`t()` function, loading `lang/*.php`) | Polylang's per-language post/field content, native to WordPress |
| `includes/language-detect.php` (Accept-Language redirect) | Polylang → Settings → "Detect browser language" (built-in toggle) |
| `set-lang.php` (cookie-race-condition-safe language switcher) | Polylang's language switcher widget/block — handles this correctly out of the box |
| The manual `hreflang` loop in `header.php` | Automatic, via Polylang + Yoast integration |
| `PAGE_BASE` / `$GLOBALS['LANGUAGES']` prefix logic | Polylang's own URL modification settings (§5.3) |

### 5.2 Install and configure languages

1. Activate Polylang, run its setup wizard.
2. Add languages: **English** (`en_US`, slug `en`), **French** (`fr_FR`, slug
   `fr`), **Chinese (Simplified)** (`zh_CN`, slug `zh`), **Japanese**
   (`ja`, slug `ja`) — matching the `html_lang` values already used in
   `includes/config.php`'s `$GLOBALS['LANGUAGES']`.
3. Set **English as the default language**.

### 5.3 Match the current URL structure

Current site: English at `/`, others at `/fr/`, `/zh/`, `/ja/`. To reproduce
this exactly:
- Polylang → Settings → URL modifications → **"The language code is added to
  all URLs, except for the default language"**. This gives `/`, `/fr/…`,
  `/zh/…`, `/ja/…` — an exact match for the current scheme, so no redirects
  are needed if the domain doesn't change.

### 5.4 Browser-language detection

Polylang → Settings → **"Detect browser language"** → enable. This is a
direct, built-in replacement for `includes/language-detect.php`, including
the same "don't override an explicit choice" behavior via Polylang's own
cookie — the custom race-condition fix in `set-lang.php` (§ prior work) isn't
needed because Polylang's switcher doesn't have that bug in the first place.

### 5.5 Translating content

For **Pages** (Home, Contact) and the **Product** post type, Polylang adds a
"Translate" action per language directly in `/wp-admin`. For each:
1. Create the English version first, fill in ACF fields.
2. Click "+" next to French → creates a linked French translation →
   fill in the **same ACF fields** with the value already sitting in
   `includes/lang/fr.php` for that string (copy-paste, not new translation
   work).
3. Repeat for Chinese (`zh.php`) and Japanese (`ja.php`).

For **ACF field translation behavior**: in ACF → Field Groups → each field
has a "Translation" setting when Polylang is active. Set text/textarea/
repeater/gallery fields to **"Translate"** (so FR/ZH/JA get independent
values) — except `SITE_LOCATION` in Theme Settings, which should be
**"Copy"** (same address in every language, matching the current site's
deliberate choice not to translate it).

### 5.6 Menus and the language switcher

Create one navigation menu per language (Polylang prompts for this), each
with translated labels (`nav.home` → "Home" / "Accueil" / "首页" / "ホーム",
already available in the lang files). Add Polylang's **Language Switcher**
widget/block to the header template, and give it the existing
`.lang-switch` class names (via the block's custom-class option, or by
wrapping it in one line of template markup) so it inherits the current
hover-frame animation and styling for free — no new CSS needed.

### 5.7 Media and alt text

Enable Polylang's media translation (Settings → Media → "Activate languages
and translations for media"). For each content photo, set the alt text once
in English and use Polylang's translated-media flow to give each language its
own alt text — the exact strings already exist as `home.filmstrip.alt1`
… `alt6`, `home.pilot.alt` / `alt2` / `alt3`, `product.how.image_alt` /
`image2_alt`, `product.cells.alt1`–`alt4` in the four lang files.

---

## 6. Migration steps (content), in order

1. WordPress installed, theme (§2) and plugins (§1) active, Polylang
   configured (§5.1–5.4).
2. Build every ACF field group (§3), export to `inc/acf-fields.php`.
3. Build `single-product.php`; create the **SiBoost** product in English,
   filling every field from `includes/lang/en.php` and the real photos.
4. Translate SiBoost into French/Chinese/Japanese (§5.5), each language's
   content coming straight from `fr.php` / `zh.php` / `ja.php`.
5. Build `front-page.php`; create the Home page (set it as the site's static
   front page under Settings → Reading) in English, then translate into the
   other three languages the same way.
6. Build `page-contact.php`; install/configure the form plugin (English form
   first, then Polylang's form-translation flow, or WPForms' native
   multilingual forms) across all four languages.
7. Configure Theme Settings (location/email/default SEO image).
8. Set SEO title/description/keywords per page **and per language** via
   Yoast — the content already exists in each lang file's `*.meta.*` keys.
9. Full QA pass (§7).
10. DNS cutover / go-live. If the domain or URL scheme changes at all,
    map old → new URLs and add 301 redirects (Home, Product, Contact ×
    4 languages = 12 URLs to check).

---

## 7. QA checklist before go-live

- [ ] All 4 languages of Home, Product, and Contact match the current
      design and copy (spot-check each against the matching `lang/*.php`
      file).
- [ ] Language switcher works correctly in both directions from every page
      (not just from Home) — this was a real bug in the original custom
      implementation (a stale-cookie race condition), so specifically test
      switching languages from a deep page like Product or Contact.
- [ ] Visiting `/` with a French/Chinese/Japanese browser language (first
      visit, no cookie) redirects correctly; a direct link to
      `/product.php`-equivalent in one language is **never** redirected to
      another language.
- [ ] Adding a second test Product (English) renders correctly, and its
      translations can be added the same way as SiBoost's.
- [ ] The three image galleries (pilot-scale, powder-to-particle,
      material-to-cell) render with the same `.filmstrip` styling/behavior
      as today, including the reveal-on-scroll animation.
- [ ] Contact form: required-field and email-format validation work in every
      language, with the correct translated error/success messages; a real
      test submission arrives at the configured address.
- [ ] `hreflang` tags, per-language canonical URLs, and `og:locale` are all
      correct — view-source and compare against what `includes/header.php`
      currently outputs.
- [ ] Favicon (SVG + PNG fallback) displays correctly across browsers.
- [ ] Mobile nav toggle, the language-switcher hover-frame animation, and
      all `prefers-reduced-motion` behavior still work as today.
- [ ] Page load speed checked (Lighthouse/PageSpeed) with caching enabled.

---

## 8. What the client can do afterward

- **Edit any text on any page, in any language** — open the Page or Product
  in `/wp-admin`, switch to the language tab, edit the field, Update.
- **Add a new product, in all 4 languages** — *Products → Add New*, fill in
  the fields from §3.2, then use Polylang's "+" to add each translation.
  Appears on the Products grid automatically.
- **Reorder or add/remove items** in "What we offer," "How it works," and any
  of the three photo galleries — repeater and gallery fields support
  drag-to-reorder and add/remove directly in `/wp-admin`.
- **Add a 5th language later** — Polylang → Languages → Add New; existing
  content can be duplicated from any language as a starting point for
  translation, no code changes needed (compare to the current site, where a
  5th language means adding a 5th `lang/xx.php` file and a `/xx/` folder by
  hand).
- **Update footer email/location, logo, or default SEO image** from one
  Theme Settings screen.

## 9. What still needs a developer afterward

- New section types or page templates (a new gallery layout, a new content
  block type).
- New Custom Post Types (e.g. "Case studies," "Team members").
- Design changes beyond what's exposed as an editable field.
- A 5th *design* language direction if it's ever RTL (Arabic, Hebrew) —
  Polylang supports RTL languages, but the current CSS was never written
  with RTL in mind and would need review.
- Plugin/theme updates and security patching — or hand this to a managed
  WordPress host / maintenance retainer.

---

## 10. Suggested timeline

| Phase | Work | Estimate |
|---|---|---|
| 1 | Theme scaffold, header/footer port, design system port | 1–2 days |
| 2 | Product CPT + ACF fields, single/archive templates | 1–2 days |
| 3 | Home + Contact templates, form plugin setup | 1–2 days |
| 4 | Polylang install/config, URL structure, browser detection, menus, language switcher styling | 1 day |
| 5 | Content + translation migration (all 4 languages × 3 pages, from the existing `lang/*.php` files), image/alt-text migration, SEO fields | 1–1.5 days |
| 6 | QA pass (§7), hosting setup, DNS cutover, redirects | 1 day |

**Total: roughly 6–9 working days.** The multilingual piece adds real time
(step 4–5), but is offset by not needing to write any new translations — the
finished, reviewed copy in `includes/lang/{en,fr,zh,ja}.php` is the source of
truth being migrated, not re-created.
