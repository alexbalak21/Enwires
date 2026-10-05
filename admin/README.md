# Enwires admin panel

A small, custom, all-PHP content editor for the main site — no WordPress, no
database, no JavaScript framework. It lets a non-technical person edit every
page's text (in all 4 languages) and manage the photo galleries, from a
browser, at `/admin/`.

---

## How it fits together

```
/content/{en,fr,zh,ja}.json   ← all editable text, one file per language
/includes/i18n.php             ← the public site reads this via t() / t_list()
/admin/                        ← this editor, writes to /content/*.json
/data/admin-users.php          ← admin login credentials (hashed)
/data/backups/                 ← automatic timestamped backup on every save
```

The public site (`index.php`, `product.php`, `contact.php`, and the `/fr/`
`/zh/` `/ja/` wrapper folders) is **unchanged** in how it calls `t('some.key')`
— only where that content is stored changed, from PHP arrays
(`includes/lang/*.php`, now removed) to JSON files edited through this admin.

---

## One-time setup

1. **Create your admin account** — there's no sign-up form; run this once
   from the server's command line (SSH or your host's terminal):
   ```
   php admin/create-admin.php <username> <password>
   ```
   Password must be at least 8 characters. Run it again any time to add
   another admin or reset a password — there's deliberately no "forgot
   password" flow in the web UI.

2. **Protect `/content/` and `/data/` at the server level.** Both folders
   already ship with an `.htaccess` that blocks direct access — but
   `.htaccess` only works on **Apache**. If this site is hosted on Nginx or
   another server, add the equivalent block rule yourself (deny all requests
   under `/content/` and `/data/`) before going live. Neither folder needs to
   be reachable by visitors — `/content/*.json` is read by PHP on the server
   side only, never fetched by the browser.

3. **Log in** at `/admin/login.php` with the account from step 1.

---

## Using the editor

- **Dashboard** (`/admin/index.php`) — pick a page (Home / Product / Contact),
  then a language.
- **Edit form** — every field is grouped by section, matching the page's
  actual layout. Switch languages any time via the tabs at the top; each
  language's content is completely independent, so editing French never
  touches English.
- **Lists** ("What we offer" items, "How it works" steps, and all three photo
  galleries) can be reordered (↑ / ↓), have rows removed (×), or have a new
  row added (the button at the bottom of that field).
- **Images** — choosing a file and clicking **Save changes** uploads it,
  validates it's a real image (not just checking the file extension), and
  re-encodes it to strip anything hidden in the file. Leave the file picker
  empty to keep the current image.
- Nothing is written to disk until you click **Save changes** — reordering,
  adding, or removing a row just redraws the form first, so you can fix a
  mistake before it's actually saved.

---

## Safety nets already built in

- **Every save is backed up** to `/data/backups/{lang}-{timestamp}.json`
  before being overwritten (the last 30 backups per language are kept,
  older ones are pruned automatically). To restore one: copy its content
  back into `/content/{lang}.json` manually (there's no restore button in
  the UI — this is meant as a safety net, not a version history feature).
- **A missing or broken translation never breaks the page.** If a language's
  `content.json` is missing a key, the site falls back to the English value,
  then to the raw key name as a last resort — never a fatal error.
- **Uploads are validated by content, not filename** — a file's real image
  data is checked (via PHP's `finfo` + GD re-encoding), not just its `.jpg`
  extension, before it's accepted.
- **CSRF protection** on every form submission.
- **Passwords are hashed** (`password_hash()`), never stored in plain text.

---

## Known limitations (by design, given the scope of this tool)

- **Adding a genuinely new field or section still needs a developer.** This
  isn't a page builder — it edits the fields already wired into
  `admin/includes/field-schema.php` and the page templates. Extending it is
  documented inline in that file.
- **An uploaded image is saved to `assets/img/` as soon as you upload it**,
  even on an intermediate "add row" / "reorder" step — if you then navigate
  away without clicking **Save changes**, that file is harmless but orphaned
  (not referenced anywhere). It's not auto-deleted, since another field
  could legitimately be using the same filename.
- **No multi-admin conflict resolution beyond file locking** — if two people
  save the same language at the exact same moment, the file write itself is
  safe (no corruption), but the second save simply overwrites the first.
  Fine for a small team; worth knowing about.
- **No visual preview inside the admin** — open the live site in another tab
  to see how a change looks.

---

## For a developer extending this later

- **Add a new editable field**: add an entry to
  `admin/includes/field-schema.php` (path, label, type), and reference it
  with `t('the.path')` or `t_list('the.path')` in the relevant page template.
  That's it — the form renders itself from the schema.
- **Add a new language**: add it to the `$languages` array in
  `admin/index.php` and `admin/edit.php`, to `$GLOBALS['LANGUAGES']` in
  `includes/config.php`, create `content/xx.json` (copy `content/en.json` as
  a starting point and translate it), and create the `/xx/` wrapper folder
  (copy `/fr/`'s three files, change `$lang = 'fr'` to `$lang = 'xx'`).
- **Field types available**: `text`, `textarea`, `repeater` (ordered list of
  sub-fields), `gallery` (ordered list of images with alt text), `image`
  (single image, optionally with a caption). See
  `admin/includes/render-fields.php` for how each renders, and
  `admin/edit.php` for how each is parsed back out of the submitted form.
