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

1. **Create your first admin account** — there's no sign-up form in the
   browser; run this once from the server's command line (SSH or your
   host's terminal):
   ```
   php admin/create-admin.php <username> <email> <password>
   ```
   Password must be at least 8 characters. After the first account is
   created, use **Users → Add admin user** inside the admin panel to add
   more — no need to use the command line again.

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

- **Dashboard** (`/admin/`) — pick a page (Home / Product / Contact),
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

## Managing admin users

Access **Users** from the top-right nav (or the header link) once logged in.

- **Add user** — click "+ Add admin user", fill in username, email,
  and password.
- **Edit user** — click "Edit" next to any account to update username,
  email, or password. Leave the password fields blank to keep the current
  password unchanged.
- **Delete user** — click "Delete" (only shown when 2+ accounts exist —
  the last account can never be deleted, so you can't lock yourself out).
- **My account** — the "My account" link in the top nav always points to
  your own edit form, so you can change your own email or password while
  logged in.

### Forgot password / reset by email

If you can't log in, go to `/admin/forgot-password.php`. Enter the email
address on the account — a reset link is emailed to it, valid for **60
minutes**. Clicking the link lets you set a new password, then redirects
to the login page.

**Important:** the reset email is sent via PHP's built-in `mail()` function.
This relies on a mail transport being configured on the server (sendmail,
postfix, or your host's outgoing mail service). On most shared-hosting plans
this works automatically. On a local dev machine it will silently not send —
to test locally, either set up a local mail catcher (Mailhog, Mailpit) or
temporarily copy the raw reset token out of `data/admin.sqlite` and
construct the URL by hand.

To switch to a real SMTP provider (Mailgun, Brevo, SendGrid, etc.) for
production — open `admin/includes/mailer.php` and replace the body of
`send_email()` with a PHPMailer or Symfony Mailer call. Nothing else in
the app needs to change.

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
- **Passwords are hashed** (`password_hash()` / bcrypt), never stored in
  plain text. The SQLite database (`data/admin.sqlite`) is blocked from
  direct HTTP access by `.htaccess`.
- **Password reset tokens** are stored as SHA-256 hashes only (the raw
  token is only ever sent by email, never stored). Each token expires after
  60 minutes and is marked used immediately on first consumption — it cannot
  be reused, and creating a new reset token for a user automatically
  invalidates any previous outstanding token for that account.
- **Timing-safe login checks** — failed login attempts take the same amount
  of time whether the username/email exists or not, so you can't tell which
  accounts are registered by measuring response time.

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
