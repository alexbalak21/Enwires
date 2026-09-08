# Enwires website

Static-style PHP site for Enwires (SiBoost), available in **English, French,
Chinese (简体中文), and Japanese**. No database, no build step — plain PHP
includes and a shared string table per language.

```
/                      ← English (default language)
/fr/                   ← French
/zh/                   ← Simplified Chinese
/ja/                   ← Japanese
```

---

## Running it locally

You need PHP installed (8.1+ recommended). From the project root:

```bash
php -S localhost:8000
```

Then open `http://localhost:8000/`. That's it — no `composer install`, no
database, no build step.

---

## File structure

```
index.php, product.php, contact.php     English pages (the "real" pages)
fr/index.php, fr/product.php, fr/contact.php   French — thin wrappers, see below
zh/...                                  Chinese — thin wrappers
ja/...                                  Japanese — thin wrappers

includes/
  config.php       Site constants, language detection, $NAV_ITEMS
  header.php       <head>, SEO tags, nav bar, language switcher
  footer.php       Footer, closing tags, JS include
  i18n.php         Loads the right language file, provides t()
  lang/
    en.php         All English text, as 'key' => 'value' pairs
    fr.php         Same keys, French text
    zh.php         Same keys, Chinese text
    ja.php         Same keys, Japanese text

assets/
  css/style.css    All styling (shared by every language)
  js/main.js       Nav toggle, scroll reveal, header shadow
  img/             Photos, logo (shared by every language)
```

### How the language folders work

There's only **one copy** of each page's logic — the file at the project
root (`index.php`, `product.php`, `contact.php`). The files under `/fr/`,
`/zh/`, `/ja/` don't duplicate that logic; each one is just three lines:

```php
<?php
$lang = 'fr';
require dirname(__DIR__) . '/index.php';
```

It sets which language to use, then hands off to the real page. This means
**you only ever edit page logic/layout in one place** (the root files) — the
language folders never need to be touched unless you're adding a whole new
page.

---

## How translation works: `t()`

Every piece of visible text on the site is looked up by a short key instead
of being hardcoded, so the same page works in all 4 languages:

```php
<h1><?php echo t('home.hero.headline'); ?></h1>
```

`t('home.hero.headline')` looks up that key in the *current* language's file
(`includes/lang/fr.php` if you're on `/fr/...`, etc.) and returns the
matching text. If a key is ever missing from a language file, it falls back
to English automatically rather than breaking the page.

The key naming pattern is `page.section.thing`, e.g.:

- `home.hero.headline`, `home.who.p1`, `home.offer.item1.title`
- `product.what.heading`, `product.how.step1.text`
- `contact.form.email_label`, `contact.form.error_name`

---

## Editing existing text

1. Open `includes/lang/en.php` (or `fr.php` / `zh.php` / `ja.php`).
2. Find the key (they're grouped by page, with comments).
3. Edit the string on the right-hand side of `=>`.
4. Save — no build step, refresh the browser.

**Important:** if you edit English text, update the same key in `fr.php`,
`zh.php`, and `ja.php` too, or those languages will silently fall back to
English for that string.

---

## Adding a new piece of text

Say you're adding a new sentence to the Home page.

1. Pick a key, e.g. `home.new_section.text`.
2. Add it to **all four** `includes/lang/*.php` files with the right
   translation in each.
3. Use it in `index.php`:
   ```php
   <p><?php echo t('home.new_section.text'); ?></p>
   ```

If you forget a language, the site won't break — it'll just show the
English text there until you fill it in.

---

## Adding a whole new page

1. Create the page at the root, e.g. `about.php`, following the pattern of
   `product.php` (set `$lang` default, require `config.php`, set
   `$pageTitle`/`$pageDescription`/`$pageKeywords` via `t()`, require
   `header.php`, page content, require `footer.php`).
2. Add its translation keys to all four `includes/lang/*.php` files.
3. Create the three wrapper files: `fr/about.php`, `zh/about.php`,
   `ja/about.php`, each just:
   ```php
   <?php
   $lang = 'fr'; // 'zh' / 'ja' for the others
   require dirname(__DIR__) . '/about.php';
   ```
4. Add it to `$NAV_ITEMS` in `includes/config.php` if it should appear in
   the nav:
   ```php
   $NAV_ITEMS = [
       'index.php'   => t('nav.home'),
       'product.php' => t('nav.product'),
       'about.php'   => t('nav.about'), // new
       'contact.php' => t('nav.contact'),
   ];
   ```
   (Add the `nav.about` key to the language files too.)

The nav, footer, language switcher, and SEO tags (canonical, hreflang, Open
Graph) all pick up new pages automatically — nothing else to wire up.

---

## Adding a 5th language

1. Copy `includes/lang/en.php` to `includes/lang/xx.php` (use the real
   2-letter code) and translate every value.
2. In `includes/config.php`, add the language to two places:
   - the validity check: `in_array($lang, ['en', 'fr', 'zh', 'ja', 'xx'], true)`
   - the `$GLOBALS['LANGUAGES']` array, following the existing pattern
     (`prefix`, `html_lang`, `og_locale`).
3. Create the folder `xx/` with the same three wrapper files as `fr/`.

The nav, language switcher, and SEO tags will pick up the new language
automatically.

---

## Notes on the pages

- **Home is `/`** (or `/fr/`, `/zh/`, `/ja/`), not `/index.php`. Every host
  and PHP's built-in dev server serve `index.php` automatically as the
  default document for a folder — no server config needed.
- **Active nav state** is matched on the actual filename being served
  (`index.php`, `product.php`, `contact.php`), not on the link text — so it
  keeps working correctly however the links themselves are written.
- **Contact form**: validates and shows success/error messages, but does
  **not actually send email yet** — see the `TODO` comment at the top of
  `contact.php`. Wire up `mail()` or an SMTP library before relying on it.
- **`SITE_URL`** in `includes/config.php` is a placeholder
  (`https://www.enwires.com/`) — update it once the real domain is live; it
  feeds the canonical URLs, hreflang tags, and Open Graph image URLs.

---

## Tech stack

Plain PHP (no framework), one shared stylesheet, vanilla JS (no build
tools, no npm). Fonts: Space Grotesk (headings) + Inter (body), loaded from
Google Fonts, with CJK system-font fallbacks for Chinese/Japanese.