# Enwires: Migration Plan to Laravel Statamic

Plan for converting the custom PHP site and admin CMS to Statamic 6. Prepared October 11, 2026.

## Overview

Statamic 6 is the current major version. It was released on January 28th, 2026 and dropped support for Laravel 11 and PHP 8.2. Laravel 13 versions are also out, so a new project should start on Statamic 6 and not v5. Check the install requirements on statamic.dev before choosing the hosting PHP version.

## 1. Decisions to make first

- **Storage:** use Statamic's default flat-file storage (content as Markdown and YAML files in the repo, versioned in Git). It fits a small brochure site and replaces the `data/backups` folder. Moving content to a database later is possible but not needed now.
- **Languages:** use Statamic's multisite feature, with one site each for EN, FR, ZH and JA. Check the current licensing page, since multisite and multiple CMS users have historically needed a paid Pro license.
- **Hosting:** PHP, Composer and SSH are required, and the web root must point at Laravel's `public/` folder. On cheap shared hosting, check this before starting.

## 2. How the current pieces map to Statamic

| Current | Statamic equivalent |
|---|---|
| `content/*.json` + `field-schema.php` | Blueprints (field definitions) and a Pages collection, with one localized entry per site |
| Repeaters (offer items, steps) | Replicator or Grid fields |
| Photo galleries and image fields | Assets fields with an asset container (replaces `uploads.php`) |
| `t()` for nav, button labels and form text | Laravel lang files, or a Global set |
| `/fr/`, `/zh/`, `/ja/` wrapper files | Multisite URL prefixes (`/fr`, `/zh`, `/ja`) |
| `header.php`, `footer.php` | Antlers layout and partials |
| `admin/` login, users, SQLite, reset emails | Built-in Control Panel users and password reset (all of `admin/` goes away) |
| `contact.php` and `contact-mailer.php` | A Statamic Form with an email notification |
| `data/backups` | Git history |

## 3. Phases

### Phase 1: Set up (about half a day)

1. Create the project with `composer create-project statamic/statamic enwires`.
2. Run it locally, create a super-admin user, and set up the 4 sites in `resources/sites.yaml`.
3. Add Git and `.env` handling for secrets.

### Phase 2: Model the content (1 to 2 days)

1. Read `admin/includes/field-schema.php`, which already lists every field per page.
2. Make a blueprint for Home, Product and Contact that mirrors it.
3. Make a Pages collection with localized entries.
4. Create a Globals set for the site name, email and location, replacing `config.php`.

### Phase 3: Migrate the content (about 1 day)

Write a one-off `artisan` command that reads each `content/{lang}.json` and creates or updates the matching entry in each site. Then spot-check all four languages. This avoids retyping anything.

### Phase 4: Rebuild the templates (2 to 3 days)

1. Convert `index.php`, `product.php` and `contact.php` into Antlers templates.
2. Move `assets/css/style.css` and `main.js` into `resources/` or `public/`. The plain CSS can be copied almost as-is.
3. Rebuild the header (with the language switcher using Statamic's localization links) and the footer.
4. Recreate the meta tags, canonical URLs and hreflang from entry fields. Statamic does not do this on its own, so use fields or an SEO addon.

### Phase 5: Contact form (about half a day)

1. Create a `contact` form with name, email, phone, subject and message fields.
2. Turn on its honeypot.
3. Add an email notification to `contact@enwires.com`. The recipient can be changed in the Control Panel or `.env`, which covers the plan to move it out of the code. Set the sending method in Laravel's `.env` mail settings. SMTP is more reliable than `mail()`.
4. Translate the success and error messages for the four languages.

### Phase 6: Cutover (about 1 day)

1. Add 301 redirects from any old URLs that change, such as `contact.php` to `/contact`.
2. Keep the existing URL structure where possible: `/`, `/fr/`, `/zh/`, `/ja/`.
3. Make `storage/` and `bootstrap/cache/` writable by the web user. This is the same permissions problem seen on the current server.
4. Deploy, run `php please stache:warm`, and enable static caching if speed is wanted.
5. Test login, editing, uploads, the contact form and all four languages in production.

## 4. Things to watch

- **Language auto-detect:** `language-detect.php` and `set-lang.php` have no built-in equivalent. Drop them, or replace them with a small middleware, since automatic redirects hurt SEO and annoy users.
- **Existing editors:** the Control Panel looks different from the current admin. A short walkthrough for whoever edits the site will save questions.
- **Rough total:** about 6 to 9 working days for a site this size, most of it in blueprints and templates.

## 5. Suggested first build steps

- A blueprint set generated from `field-schema.php`.
- The JSON-to-entries migration command.