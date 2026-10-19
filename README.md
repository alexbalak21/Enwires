# Enwires — website & back-office / site web & back-office

**Languages / Langues :** [English](#english) · [Français](#français)

*Last updated / Dernière mise à jour : 2026-10-19.* This file is shown inside the admin at `/admin/lisez-moi.php` (English / Français switch). Edit it here, it updates there. / Ce fichier s'affiche dans l'administration à `/admin/lisez-moi.php` (bouton English / Français). Modifiez-le ici, la page se met à jour.

<!-- lang:en -->
## English

### At a glance

Enwires is a small, hand-written **PHP website** with a **custom back-office** (no WordPress, no framework, no Composer).

- **4 languages:** English (no prefix), French `/fr/`, Chinese `/zh/`, Japanese `/ja/`.
- **3 public pages** (Home, Product, Contact) plus a **404 page**, all with clean URLs (`/`, `/product`, `/contact`).
- **Pages are built from blocks** (Home, Product, Contact and the 404 page). In the admin you add, reorder and edit blocks on the left and see the real page update live on the right.
- **The header and footer are editable** once, for all pages.
- **Content is stored in JSON files** (not in a database). Only admin accounts are in a small SQLite database.
- **Contact form** by email. In **Email settings** you choose who receives the messages and who they are sent from, and there is a built-in **mail test**.
- **A System check** page tells you exactly why saving or mailing fails on a server.

### Requirements

| What | Needed |
|---|---|
| Web server | **Apache** with `mod_rewrite` and `.htaccess` allowed (on Nginx, equivalent rules must be written by hand) |
| PHP | 7.4 or newer (8.x recommended; developed on 8.4) |
| PHP extensions | `pdo_sqlite`, `gd` (JPEG, PNG, WebP), `fileinfo`, `mbstring`, `json`, `openssl` (for encrypted SMTP) |
| Write access | PHP must be able to write in `content/`, `data/` and `assets/img/` |
| HTTPS | Strongly recommended (see Security) |

### Quick start (first installation)

1. **Upload** all the files to the web root of the site.
2. **Check that clean URLs work:** open `/product`. If you get an Apache error page instead of the site, `mod_rewrite` / `.htaccess` is not active.
3. **Create the first admin account.** There is no sign-up page. Over SSH, in the site folder:

```
php admin/create-admin.php <username> <email> <password>
```

The password needs at least 8 characters. No SSH? Run the same command on your computer, then upload the resulting `data/admin.sqlite`.

4. **Log in** at `/admin/`.
5. **Convert to blocks (one time).** If the dashboard shows a red banner, click **Convert now**. This turns the existing Home and Product content into blocks and backs up every file first.
6. **Run the System check** (`/admin/system-check.php`) and press **Run the full test**. Everything that must be writable should be green.
7. **Set up email:** in **Email settings** (top menu) choose who receives the contact messages and the sender address, then press **Send a test email**. If nothing arrives, open **Mail check** on the System check page: when a setting works, copy it into `includes/mail-config.php`.
8. **Edit `includes/config.php`:** set `SITE_URL` (your real address, with `https://`), `SITE_EMAIL` and `SITE_LOCATION`.

For local development: `php -S localhost:8000 router.php` (the built-in PHP server ignores `.htaccess`, so it needs `router.php`).

### How the website works

#### URLs and the router

Every request that is not a real file goes through `router.php`, which decides what to show.

| URL | Shows |
|---|---|
| `/` | Home (English) |
| `/product` | Product |
| `/contact` | Contact |
| `/fr/`, `/fr/product`, `/fr/contact` | The same pages in French (same with `/zh/` and `/ja/`) |
| Anything else | The **404 page** (with a real 404 status code, hidden from search engines) |

Old addresses such as `/product.php` or `/fr/contact.php` are redirected (301) to the clean URL. `/product/` and `/Product` are redirected to `/product`. Missing images or scripts get a plain "404 Not Found" instead of a whole page.

The router is the file `router.php`; the Apache rules are in `.htaccess`.

#### Languages

- **English has no prefix; French, Chinese and Japanese use `/fr/`, `/zh/`, `/ja/`.**
- **First visit to `/`:** the visitor is sent to their language, taken from the cookie `enwires_lang` if it exists, otherwise from the browser's language. This only happens on the English home page, never on deep links.
- **Manual choice always wins:** the language switcher goes through `set-lang.php`, which stores the choice in the cookie (1 year).
- **Missing translation:** an empty text in a non-English language shows the English text instead. A page never breaks because a translation is missing.

#### How a page is built

1. `router.php` chooses the template: `index.php`, `product.php`, `contact.php` or `404.php`.
2. The template loads `includes/config.php` (site constants, language) and then `includes/header.php`.
3. The header prints the SEO tags and the **site header** (logo, menu, language switcher) from the Header & footer settings.
4. The page body is made of **blocks** (`enw_render_blocks()`), or, for Contact, the contact form.
5. `includes/footer.php` prints the **site footer**.

Each block type has a small template in `includes/blocks/`.

#### SEO

Every page gets a title, description, keywords, canonical URL, `hreflang` links for all languages, and Open Graph / Twitter tags. Title, description and keywords are edited per page and per language in the admin. The 404 page is marked `noindex`. There is no `sitemap.xml` or `robots.txt` yet.

### Where the data lives

| What | Where |
|---|---|
| Translated texts of the blocks, SEO text, Contact page text, header/footer texts | `content/en.json`, `fr.json`, `zh.json`, `ja.json` |
| Block structure of each page (order, types, photos, options) | `content/structure.json` |
| Header/footer settings shared by all languages, contact recipient, mail sender | `content/site.json` (created on first save) |
| Admin accounts and password-reset tokens | `data/admin.sqlite` |
| Automatic backups | `data/backups/` |
| Photos | `assets/img/` |
| Mail settings (optional) | `includes/mail-config.php` |

**The rule "shared structure, translated text":** the order of blocks, the photos, the links and the options are the **same in every language** and are stored once. Only the **texts** (and photo descriptions) are translated, one set per language. So changing a photo or moving a block once changes it for all four languages.

Old keys (`home.*`, `product.*`, `nav.*`, `site.tagline`) may still exist in the JSON files from before the block builder. They are kept as a safety net and are no longer used by the pages. `data/admin-users.php` and `includes/lang/*.php` are also leftovers and are not used.

The `content/`, `data/` and `includes/` folders are blocked from the web by `.htaccess`, so visitors cannot download them.

### The back-office (admin)

#### Logging in

Go to `/admin/` and log in with your username **or** email. The session ends when the browser closes. **Forgot password?** use the link on the login page (see Users and passwords).

#### The dashboard

`/admin/` shows one card per area, each with the four languages:

- **Home**, **Product** and **Contact:** open the block builder (with live preview).
- **Header & footer:** one editor for all pages.
- **404 page:** block builder for the "not found" page.
- **Email settings:** who receives the contact messages and who they are sent from.
- **System check:** diagnostics.
- **Help:** this documentation.

#### The block builder

Open a page and a language, for example Home in French. The screen has two parts.

**Left: the blocks and their content.**

- **Page settings (SEO)** at the top: title (about 60 characters), description (about 160) and keywords, with counters.
- A card for each block. Click its header to open or close it.
- `⋮⋮` drag to reorder, **↑ ↓** move, **⧉** duplicate, **×** delete.
- **+ Add block here** (between blocks) or **+ Add block** (at the bottom) opens the list of block types.

**Right: the real page, live.** It refreshes about a quarter of a second after you type, using the same templates as the public site. You can click a block in the preview to jump to its form, hover a card to highlight the block, and switch the preview width (Desktop / Tablet / Mobile).

**Saving.** Nothing is stored until you press **Save** (or Ctrl/Cmd + S). The page warns you if you leave with unsaved changes. If someone else saved the same page meanwhile, you get a message and must reload, so you never overwrite their work by accident.

**Good to know**

- **Languages:** switch with the language tabs at the top. When you edit French, leave a text empty to use the English text (it appears greyed out as a hint).
- **Shared changes:** moving, adding or deleting a block, changing a photo, a background or a link applies to **all** languages. Deleting asks for confirmation for this reason.
- **Duplicating** copies only the text of the language you are in. Duplicate in English first.
- **Photos:** upload JPEG, PNG or WebP up to 10 MB (the server may allow less), or choose one already on the site. Add a short **alt text** describing each photo (good for accessibility and SEO).
- **Rich text:** in text fields you can type `&mdash;`, `&ndash;`, `&times;` and similar entities, and use `<strong>`, `<em>` and `<br>`. Any other HTML is shown as plain text for safety.
- **Limits:** 60 blocks per page, 40 items per list, 8,000 characters per text.
- **Hero background:** the Hero block has its own background settings. **Default photo** keeps the picture from the stylesheet; **My own photo** lets you upload or choose one; **A solid color** uses a color picker. For photos you can set the **darkening** (none, soft, normal, strong) so the text stays readable, and you can switch the **text color** to dark when the background is light. Until you change something, the Hero looks exactly as before.

#### Block types

| Block | What it shows | Fields |
|---|---|---|
| **Hero** | Big opening section | Eyebrow, headline, text, primary button (label + link), secondary button (label + link), background (default photo, your own photo or a color), darkening, text color |
| **Page intro** | Compact page title | Eyebrow, title, text, two layout switches (full-width title, less space below) |
| **Ribbon divider** | Decorative line | none |
| **Text + big number** | Two paragraphs beside a statistic | Heading, paragraph 1 and 2, big number, caption, layout (text left or number left), background |
| **Cards** | Titled short cards | Heading, cards (title + text), background |
| **Image strip** | Row of photos | Heading, text, photos (with alt text), background, remove space below |
| **Photo with caption** | One large photo | Heading, text, photo (alt text + caption), background |
| **Steps + photos** | Numbered steps beside photos | Heading, steps (title + text), photos, background |
| **Contact form + details** | The contact form with the location and email beside it | Every label, the error messages and the success message, show or hide the details, background |
| **Call to action** | Closing message with a button | Heading, text, button label, button link, background |

Backgrounds: **Grey**, **Charcoal (dark)** or **Paper (light)**. A button can link to Home, Product or Contact.

#### Header & footer

`/admin/layout.php`: one editor for **every page**, with a live preview you can point at Home, Product, Contact or the 404 page.

- **Header:** logo (upload or choose), logo description, brand name, tagline, mobile menu label, show or hide the language switcher, and the **navigation links** (add, reorder, remove, up to 12). A link goes to a page of the site or to a custom address (`https://…`, `/path`, `mailto:…`, `tel:…`), optionally in a new tab.
- **Footer:** location, email (leave empty to hide), the same links as the header or its own list, and the copyright line (`{year}` becomes the current year).

Link addresses, logo and options are shared by all languages; link labels and texts are translated per language. Until you save once, the site keeps using the defaults from `includes/config.php`.

#### The Contact page

Built with blocks like Home and Product, with the same live preview (`/admin/builder.php?page=contact`). It has an introduction (**Page intro** block) and a **Contact form + details** block. In that block you edit every label of the form, the error messages (name missing, email not valid, sending failed) and the success message, and you can hide the location/email details. The form's behaviour (checks, spam trap, sending) is fixed in the code. The first time you open the page it shows your existing texts; press **Save** to keep it as blocks.

#### The 404 page

Built with blocks like any page (`/admin/builder.php?page=notfound`). Until you save it, a built-in text is shown in all four languages. The first time you open it, press **Save** to keep it and start editing.

#### Email settings

`/admin/email-settings.php` (**Email** in the top menu) controls the mail sent by the contact form:

- **Send contact-form messages to:** the address that receives them. A typo means lost messages, so it is checked and must be a valid address.
- **Send from (sender address):** the address the message is sent from. It should be a **real mailbox of your own domain**; mail servers reject or hide mail from invented addresses. Empty means automatic (`contact@your-domain`, or the SMTP login).
- **Sender name:** the name shown next to the address in the inbox.
- **Send a test email** sends a real message to the recipient with the saved settings and shows the result and the conversation with the mail server.

The page also shows what a message will look like (From / To / Reply-To) and warns when the sender is not on your site's domain. How the mail is **delivered** (PHP `mail()` or SMTP, and the SMTP password) stays in `includes/mail-config.php`, because a password does not belong in a web form; try it in System check → Mail check. The address displayed **on the website** (footer and Contact page) is a different setting, in Header & footer.

#### Users and passwords

- **`/admin/users.php`:** add, edit or delete admin accounts. The last account can never be deleted, so you cannot lock yourself out. **My account** (top menu) edits your own details. Leave the password empty to keep it.
- **Forgot password:** `/admin/forgot-password.php` emails a link valid for **60 minutes**, usable once. The page always shows the same message whether the address exists or not.
- Reset emails use the same mail system as the contact form, and the link is built from `SITE_URL` + `/admin`.

#### Convert to blocks (one time)

`/admin/migrate-blocks.php` turns the old Home and Product content into blocks. It reads your current content, backs up every file first, leaves the old keys untouched and refuses to run twice. Until it has run, the public Home and Product pages are empty.

#### System check

`/admin/system-check.php` has two parts.

- **Mail check:** sends a real test email (with PHP `mail()` or with SMTP settings you type), shows the full conversation with the mail server, tests whether the server can reach it, looks at your domain's SPF and DMARC records, explains any error in plain words, and gives you the `mail-config.php` to use.
- **File & SQLite check:** tries 15 file operations in every folder, rewrites existing files with themselves (no change), tests your real database and new test databases in each folder, and explains what it finds. Every result can be copied as a text report to send to your host.

#### This help page

`/admin/lisez-moi.php` shows this document in English or French. It reads `README.md`, so update the file to update the page.

### Contact form and email

1. The visitor fills in name, email, message (and optionally phone and subject). A hidden field catches simple spam robots.
2. The message is sent **from your own domain** (mail servers reject mail pretending to come from a visitor's address). The visitor's address is in **Reply-To**, so pressing Reply answers them.
3. The **recipient** and the **sender** are set in **Email settings**. Until they are saved, the recipient is `SITE_EMAIL` from `includes/config.php` and the sender is automatic.
4. If sending fails, the visitor sees an error that mentions your email address, and the reason is written to the server's error log.

**How it sends** is set in `includes/mail-config.php` (copy `includes/mail-config.sample.php`):

- `transport` `mail`: PHP's own `mail()`. Simple, but it only tells you that the server accepted the message, not that it was delivered.
- `transport` `smtp`: a real mail server (your host's, or Brevo, Mailjet, Gmail, …). Reliable, and errors are explained.
- `from_address`: the default sender, used when none is saved in Email settings (which wins). Should be a real mailbox of your domain. Empty means automatic (the SMTP login if it is an address, otherwise `contact@your-domain`).

Use **Admin → System check → Mail check** to find settings that work before writing them down. For good delivery your domain should have SPF (and ideally DKIM and DMARC) records for the service that sends the mail.

### Backups and restoring

Every save copies the previous file to `data/backups/` first:

- `en-20261018-143000.json` (one series per language), `structure-….json`, `site-….json`.
- The latest 30 of each series are kept; older ones are deleted automatically.

**To restore:** copy the backup over the current file in `content/` (rename it to `en.json`, `structure.json`, …) with FTP or SSH. There is no restore button. Download `content/` and `data/` regularly: they contain all your content and accounts.

### Security

In place:

- Passwords are stored hashed (bcrypt). Login timing does not reveal which accounts exist.
- CSRF protection on every form and every save; the session cookie is `HttpOnly` and limited to `/admin/`.
- Reset tokens are stored only as hashes, expire after 60 minutes and work once.
- Uploads are checked by their real content, then re-encoded (which removes hidden data).
- Text from the admin is escaped before display; only a short safe list of entities and tags is allowed.
- Admin pages are marked `noindex`. `content/`, `data/`, `includes/` and `.md` files are blocked from the web.

To do on a live site:

- **Use HTTPS** and, in `admin/includes/auth.php`, enable the line `'secure' => true` for the session cookie.
- **Limit access to `/admin/`** if you can (for example by IP at the host).
- There is **no limit on login attempts yet** and no CAPTCHA on the contact form. Use strong passwords.
- Keep backups of `content/` and `data/`.

### Updating the site

1. Download `content/`, `data/` and `includes/mail-config.php` as a backup.
2. Upload the new files **over** the old ones. Never overwrite `content/`, `data/`, `assets/img/` or `includes/mail-config.php` unless you mean to.
3. Check `includes/config.php`: do not replace it with a version that has different `SITE_URL` / `SITE_EMAIL`.
4. Open the System check and run the tests again.

### Troubleshooting

| Problem | Likely cause and fix |
|---|---|
| Home / Product pages show only the header and footer | The site is not converted to blocks yet: **Convert now** on the dashboard (or `/admin/migrate-blocks.php`) |
| "Permission denied" when saving or uploading | PHP cannot write there: run **System check → File & SQLite check**. It shows the exact folder, the rights and what to do |
| Rights are already 777 and it still fails | The host blocks writing for PHP (write protection or sandbox): check the hosting panel or ask support, and send them the System check report |
| `/product` gives a server 404 or error page | `mod_rewrite` / `.htaccess` is not active: ask the host to enable `AllowOverride` |
| Contact messages do not arrive | Check the recipient in **Email settings** and press **Send a test email**. Then check the spam folder, run **Mail check**, and try SMTP |
| Reset email does not arrive | Same mail system: use **Mail check**. The link uses `SITE_URL` + `/admin` |
| Visitors are sent to the wrong language | Their cookie `enwires_lang` or browser language decides; they can switch with the language menu |
| "Someone else saved this page" | Another admin saved first: reload the page, then redo your edit |
| An image is not displayed | Check the file exists in `assets/img/` and that the alt text and file name are fine |
| 413 / upload error | The file is larger than the server limit: shrink the image (the System check shows `upload_max_filesize`) |

### For developers

**Code map**

```
router.php            clean URLs, language prefixes, 404
.htaccess             sends requests to router.php, blocks private folders
index.php  product.php  contact.php  404.php     page templates
includes/config.php   site constants, language setup (edit SITE_URL, SITE_EMAIL here)
includes/header.php  footer.php    site header / footer (read the layout settings)
includes/blocks/*.php one template per block type
includes/block-registry.php   block types and their fields (single source of truth)
includes/blocks-core.php      block data: load, split, English fallback, defaults
includes/layout-core.php      header/footer data
includes/mailer.php           sending email (mail() or SMTP)
includes/contact-mailer.php   builds the contact email
admin/builder.php  preview.php  layout.php  email-settings.php  users.php  system-check.php ...
admin/includes/*-store.php    saving with backups and conflict checks
```

**Add a block type.** Add an entry in `includes/block-registry.php` (label, fields), then create `includes/blocks/<type>.php` to render it. The builder shows it automatically. Field types: `text`, `textarea`, `select`, `link`, `checkbox`, `image`, `gallery`, `repeater`.

**Add a field to the contact form.** Add the text field to the `contact-form` entry in `includes/block-registry.php`, print it in `includes/blocks/contact-form.php`, and read it in `contact.php`. The form's state (errors, submitted) is passed to the block in `$GLOBALS['enw_contact']`.

**Add a page.** Add it to `enw_site_pages()` in `includes/block-registry.php`, create its template, and add its slug to `$r_pages` in `router.php`. For a block page, also add it to `enw_block_pages()`.

**Add a language.** This touches several lists: `$GLOBALS['LANGUAGES']` and the check in `includes/config.php`; the language lists in `includes/blocks-core.php`, `admin/includes/content-store.php` and `admin/includes/blocks-store.php`; the three rules in `.htaccess` and the patterns in `router.php`; the `$languages` arrays in the admin pages; and a new `content/xx.json` (copy `en.json`).

**Move content to a database later.** Reading and writing go through a few functions: `enw_lang_content()`, `enw_structure_load()`, `enw_site_load()` and the `cs_*`, `bs_*`, `ls_*` save helpers. Replace those, not the templates.

**Naming.** `enw_*` public site, `cs_*` content store, `bs_*` block store, `ls_*` layout store, `sc_*` system check.

### Known limitations

- No draft / publish: a save goes live immediately.
- No restore button for backups (manual copy).
- Uploaded images are not resized and are not deleted when unused; the camera rotation tag is lost on re-encoding.
- No login-attempt limit and no CAPTCHA.
- Content is in JSON files, so saving needs write access; one save at a time rewrites a whole file (a conflict check prevents overwriting someone else's work).
- The contact form's behaviour (fields, checks, spam trap) is fixed in the code; only its texts and layout are editable.
- No `sitemap.xml` / `robots.txt` generation yet.

<!-- lang:fr -->
## Français

### En bref

Enwires est un petit **site PHP écrit à la main** avec un **back-office sur mesure** (ni WordPress, ni framework, ni Composer).

- **4 langues :** anglais (sans préfixe), français `/fr/`, chinois `/zh/`, japonais `/ja/`.
- **3 pages publiques** (Accueil, Produit, Contact) plus une **page 404**, avec des URL propres (`/`, `/product`, `/contact`).
- **Les pages sont construites avec des blocs** (Accueil, Produit, Contact et la page 404). Dans l'administration, vous ajoutez, déplacez et modifiez les blocs à gauche, et vous voyez la vraie page se mettre à jour en direct à droite.
- **L'en-tête et le pied de page sont modifiables** une seule fois, pour toutes les pages.
- **Le contenu est stocké dans des fichiers JSON** (pas dans une base de données). Seuls les comptes administrateur sont dans une petite base SQLite.
- **Formulaire de contact** par e-mail. Dans **Email settings**, vous choisissez qui reçoit les messages et depuis quelle adresse ils sont envoyés, et un **test d'envoi** est intégré.
- **Une page « Contrôle système »** vous dit exactement pourquoi l'enregistrement ou l'envoi d'e-mails échoue sur un serveur.

### Prérequis

| Quoi | Nécessaire |
|---|---|
| Serveur web | **Apache** avec `mod_rewrite` et `.htaccess` autorisés (sur Nginx, il faut écrire les règles équivalentes à la main) |
| PHP | 7.4 ou plus récent (8.x recommandé ; développé sous 8.4) |
| Extensions PHP | `pdo_sqlite`, `gd` (JPEG, PNG, WebP), `fileinfo`, `mbstring`, `json`, `openssl` (pour le SMTP chiffré) |
| Droits d'écriture | PHP doit pouvoir écrire dans `content/`, `data/` et `assets/img/` |
| HTTPS | Fortement recommandé (voir Sécurité) |

### Démarrage rapide (première installation)

1. **Envoyez** tous les fichiers à la racine web du site.
2. **Vérifiez que les URL propres fonctionnent :** ouvrez `/product`. Si vous obtenez une page d'erreur Apache au lieu du site, `mod_rewrite` / `.htaccess` n'est pas actif.
3. **Créez le premier compte administrateur.** Il n'y a pas de page d'inscription. En SSH, dans le dossier du site :

```
php admin/create-admin.php <identifiant> <email> <mot-de-passe>
```

Le mot de passe doit faire au moins 8 caractères. Pas de SSH ? Lancez la même commande sur votre ordinateur, puis envoyez le fichier `data/admin.sqlite` obtenu.

4. **Connectez-vous** sur `/admin/`.
5. **Convertissez en blocs (une seule fois).** Si le tableau de bord affiche un bandeau rouge, cliquez sur **Convert now**. Cela transforme le contenu existant de l'Accueil et du Produit en blocs, après avoir sauvegardé chaque fichier.
6. **Lancez le Contrôle système** (`/admin/system-check.php`) et cliquez sur **Run the full test**. Tout ce qui doit être modifiable doit être au vert.
7. **Configurez l'e-mail :** dans **Email settings** (menu du haut), choisissez qui reçoit les messages de contact et l'adresse d'expédition, puis cliquez sur **Send a test email**. Si rien n'arrive, ouvrez **Mail check** sur la page Contrôle système : quand un réglage fonctionne, copiez-le dans `includes/mail-config.php`.
8. **Modifiez `includes/config.php` :** renseignez `SITE_URL` (votre vraie adresse, avec `https://`), `SITE_EMAIL` et `SITE_LOCATION`.

En développement local : `php -S localhost:8000 router.php` (le serveur PHP intégré ignore `.htaccess`, il a donc besoin de `router.php`).

### Comment fonctionne le site

#### Les URL et le routeur

Toute requête qui ne correspond pas à un vrai fichier passe par `router.php`, qui décide quoi afficher.

| URL | Affiche |
|---|---|
| `/` | Accueil (anglais) |
| `/product` | Produit |
| `/contact` | Contact |
| `/fr/`, `/fr/product`, `/fr/contact` | Les mêmes pages en français (idem avec `/zh/` et `/ja/`) |
| Tout le reste | La **page 404** (avec un vrai code 404, masquée aux moteurs de recherche) |

Les anciennes adresses comme `/product.php` ou `/fr/contact.php` sont redirigées (301) vers l'URL propre. `/product/` et `/Product` sont redirigées vers `/product`. Une image ou un script introuvable renvoie un simple « 404 Not Found » et non une page entière.

Le routeur est le fichier `router.php` ; les règles Apache sont dans `.htaccess`.

#### Les langues

- **L'anglais n'a pas de préfixe ; le français, le chinois et le japonais utilisent `/fr/`, `/zh/`, `/ja/`.**
- **Première visite sur `/` :** le visiteur est envoyé vers sa langue, lue dans le cookie `enwires_lang` s'il existe, sinon dans la langue du navigateur. Cela n'arrive que sur l'accueil anglais, jamais sur un lien direct.
- **Le choix manuel gagne toujours :** le sélecteur de langue passe par `set-lang.php`, qui enregistre le choix dans le cookie (1 an).
- **Traduction manquante :** un texte vide dans une langue autre que l'anglais affiche le texte anglais. Une page ne casse jamais à cause d'une traduction manquante.

#### Comment une page est construite

1. `router.php` choisit le modèle : `index.php`, `product.php`, `contact.php` ou `404.php`.
2. Le modèle charge `includes/config.php` (constantes du site, langue) puis `includes/header.php`.
3. L'en-tête affiche les balises SEO et l'**en-tête du site** (logo, menu, sélecteur de langue) à partir des réglages « Header & footer ».
4. Le corps de la page est fait de **blocs** (`enw_render_blocks()`), ou, pour Contact, du formulaire de contact.
5. `includes/footer.php` affiche le **pied de page**.

Chaque type de bloc a un petit modèle dans `includes/blocks/`.

#### SEO

Chaque page reçoit un titre, une description, des mots-clés, une URL canonique, des liens `hreflang` pour toutes les langues, et les balises Open Graph / Twitter. Le titre, la description et les mots-clés se modifient par page et par langue dans l'administration. La page 404 est en `noindex`. Il n'y a pas encore de `sitemap.xml` ni de `robots.txt`.

### Où sont les données

| Quoi | Où |
|---|---|
| Textes traduits des blocs, texte SEO, textes de la page Contact, textes de l'en-tête et du pied de page | `content/en.json`, `fr.json`, `zh.json`, `ja.json` |
| Structure des blocs de chaque page (ordre, types, photos, options) | `content/structure.json` |
| Réglages de l'en-tête et du pied de page communs à toutes les langues, destinataire du formulaire, expéditeur des e-mails | `content/site.json` (créé au premier enregistrement) |
| Comptes administrateur et jetons de réinitialisation | `data/admin.sqlite` |
| Sauvegardes automatiques | `data/backups/` |
| Photos | `assets/img/` |
| Réglages e-mail (facultatif) | `includes/mail-config.php` |

**La règle « structure partagée, texte traduit » :** l'ordre des blocs, les photos, les liens et les options sont **identiques dans toutes les langues** et ne sont enregistrés qu'une fois. Seuls les **textes** (et les descriptions de photos) sont traduits, un jeu par langue. Changer une photo ou déplacer un bloc une fois le change donc pour les quatre langues.

D'anciennes clés (`home.*`, `product.*`, `nav.*`, `site.tagline`) peuvent encore exister dans les fichiers JSON d'avant l'éditeur de blocs. Elles sont conservées par précaution et ne sont plus utilisées par les pages. `data/admin-users.php` et `includes/lang/*.php` sont aussi des restes inutilisés.

Les dossiers `content/`, `data/` et `includes/` sont bloqués depuis le web par `.htaccess` : les visiteurs ne peuvent pas les télécharger.

### Le back-office (administration)

#### Se connecter

Allez sur `/admin/` et connectez-vous avec votre identifiant **ou** votre e-mail. La session se termine à la fermeture du navigateur. **Mot de passe oublié ?** utilisez le lien de la page de connexion (voir Utilisateurs et mots de passe).

#### Le tableau de bord

`/admin/` affiche une carte par zone, chacune avec les quatre langues :

- **Home**, **Product** et **Contact :** ouvrent l'éditeur de blocs (avec aperçu en direct).
- **Header & footer :** un seul éditeur pour toutes les pages.
- **404 page :** éditeur de blocs de la page « introuvable ».
- **Email settings :** qui reçoit les messages de contact et depuis quelle adresse ils sont envoyés.
- **System check :** diagnostics.
- **Help :** cette documentation.

#### L'éditeur de blocs

Ouvrez une page et une langue, par exemple l'Accueil en français. L'écran a deux parties.

**À gauche : les blocs et leur contenu.**

- **Page settings (SEO)** en haut : titre (environ 60 caractères), description (environ 160) et mots-clés, avec des compteurs.
- Une carte par bloc. Cliquez sur son en-tête pour l'ouvrir ou la fermer.
- `⋮⋮` glisser pour réordonner, **↑ ↓** déplacer, **⧉** dupliquer, **×** supprimer.
- **+ Add block here** (entre deux blocs) ou **+ Add block** (en bas) ouvre la liste des types de blocs.

**À droite : la vraie page, en direct.** Elle se rafraîchit environ un quart de seconde après votre frappe, avec les mêmes modèles que le site public. Vous pouvez cliquer sur un bloc dans l'aperçu pour aller à son formulaire, survoler une carte pour surligner le bloc, et changer la largeur de l'aperçu (Desktop / Tablet / Mobile).

**Enregistrer.** Rien n'est enregistré avant d'appuyer sur **Save** (ou Ctrl/Cmd + S). La page vous prévient si vous la quittez avec des modifications non enregistrées. Si quelqu'un d'autre a enregistré la même page entre-temps, vous recevez un message et devez recharger : vous n'écrasez jamais son travail par erreur.

**À savoir**

- **Langues :** changez avec les onglets en haut. Quand vous modifiez le français, laissez un texte vide pour utiliser le texte anglais (il apparaît en grisé comme indication).
- **Changements partagés :** déplacer, ajouter ou supprimer un bloc, changer une photo, un fond ou un lien s'applique à **toutes** les langues. La suppression demande une confirmation pour cette raison.
- **Dupliquer** ne copie que le texte de la langue en cours. Dupliquez d'abord en anglais.
- **Photos :** envoyez du JPEG, PNG ou WebP jusqu'à 10 Mo (le serveur peut en autoriser moins), ou choisissez une photo déjà sur le site. Ajoutez un court **texte alternatif** décrivant chaque photo (utile pour l'accessibilité et le SEO).
- **Texte enrichi :** dans les champs de texte, vous pouvez saisir `&mdash;`, `&ndash;`, `&times;` et entités semblables, et utiliser `<strong>`, `<em>` et `<br>`. Tout autre HTML est affiché comme du texte simple par sécurité.
- **Limites :** 60 blocs par page, 40 éléments par liste, 8 000 caractères par texte.
- **Fond du Hero :** le bloc Hero a ses propres réglages de fond. **Default photo** conserve l'image de la feuille de style ; **My own photo** permet d'envoyer ou de choisir une photo ; **A solid color** utilise un sélecteur de couleur. Pour les photos, vous réglez l'**assombrissement** (aucun, léger, normal, fort) pour que le texte reste lisible, et vous pouvez passer la **couleur du texte** en sombre quand le fond est clair. Tant que vous ne changez rien, le Hero reste exactement comme avant.

#### Types de blocs

| Bloc | Ce qu'il affiche | Champs |
|---|---|---|
| **Hero** | Grande section d'ouverture | Surtitre, titre, texte, bouton principal (libellé + lien), bouton secondaire (libellé + lien), fond (photo par défaut, votre photo ou une couleur), assombrissement, couleur du texte |
| **Page intro** | Titre de page compact | Surtitre, titre, texte, deux options de mise en page (titre pleine largeur, moins d'espace en dessous) |
| **Ribbon divider** | Ligne décorative | aucun |
| **Text + big number** | Deux paragraphes à côté d'un chiffre clé | Titre, paragraphes 1 et 2, grand chiffre, légende, disposition (texte à gauche ou chiffre à gauche), fond |
| **Cards** | Petites cartes titrées | Titre, cartes (titre + texte), fond |
| **Image strip** | Rangée de photos | Titre, texte, photos (avec texte alternatif), fond, supprimer l'espace en dessous |
| **Photo with caption** | Une grande photo | Titre, texte, photo (texte alternatif + légende), fond |
| **Steps + photos** | Étapes numérotées à côté de photos | Titre, étapes (titre + texte), photos, fond |
| **Contact form + details** | Le formulaire de contact avec la localisation et l'e-mail à côté | Chaque libellé, les messages d'erreur et le message de succès, afficher ou masquer les coordonnées, fond |
| **Call to action** | Message de fin avec un bouton | Titre, texte, libellé du bouton, lien du bouton, fond |

Fonds : **Grey** (gris), **Charcoal (dark)** (anthracite, sombre) ou **Paper (light)** (papier, clair). Un bouton peut pointer vers Accueil, Produit ou Contact.

#### En-tête et pied de page

`/admin/layout.php` : un seul éditeur pour **toutes les pages**, avec un aperçu en direct que vous pouvez afficher sur l'Accueil, le Produit, le Contact ou la page 404.

- **En-tête :** logo (envoi ou choix), description du logo, nom de marque, slogan, libellé du bouton de menu mobile, afficher ou masquer le sélecteur de langue, et les **liens de navigation** (ajout, réordonnancement, suppression, jusqu'à 12). Un lien pointe vers une page du site ou vers une adresse libre (`https://…`, `/chemin`, `mailto:…`, `tel:…`), éventuellement dans un nouvel onglet.
- **Pied de page :** localisation, e-mail (laisser vide pour le masquer), les mêmes liens que l'en-tête ou une liste propre, et la ligne de copyright (`{year}` devient l'année en cours).

Les adresses des liens, le logo et les options sont communs à toutes les langues ; les libellés et les textes sont traduits par langue. Tant que vous n'avez pas enregistré une fois, le site garde les valeurs par défaut de `includes/config.php`.

#### La page Contact

Construite avec des blocs comme l'Accueil et le Produit, avec le même aperçu en direct (`/admin/builder.php?page=contact`). Elle comprend une introduction (bloc **Page intro**) et un bloc **Contact form + details**. Dans ce bloc, vous modifiez chaque libellé du formulaire, les messages d'erreur (nom manquant, e-mail non valide, échec de l'envoi) et le message de succès, et vous pouvez masquer les coordonnées. Le comportement du formulaire (contrôles, piège à spam, envoi) est fixé dans le code. À la première ouverture, la page affiche vos textes existants ; cliquez sur **Save** pour la conserver sous forme de blocs.

#### La page 404

Construite avec des blocs comme les autres pages (`/admin/builder.php?page=notfound`). Tant que vous ne l'avez pas enregistrée, un texte intégré s'affiche dans les quatre langues. À la première ouverture, cliquez sur **Save** pour la conserver et commencer à la modifier.

#### Réglages e-mail

`/admin/email-settings.php` (**Email** dans le menu du haut) contrôle les e-mails envoyés par le formulaire de contact :

- **Send contact-form messages to :** l'adresse qui les reçoit. Une faute de frappe fait perdre des messages : l'adresse est donc contrôlée et doit être valide.
- **Send from (sender address) :** l'adresse depuis laquelle le message est envoyé. Elle doit être une **vraie boîte de votre propre domaine** ; les serveurs de messagerie rejettent ou masquent les messages venant d'adresses inventées. Vide = automatique (`contact@votre-domaine`, ou l'identifiant SMTP).
- **Sender name :** le nom affiché à côté de l'adresse dans la boîte de réception.
- **Send a test email** envoie un vrai message au destinataire avec les réglages enregistrés et affiche le résultat et la conversation avec le serveur de messagerie.

La page montre aussi à quoi ressemblera un message (From / To / Reply-To) et vous avertit quand l'expéditeur n'est pas sur le domaine de votre site. La façon dont le courrier est **distribué** (`mail()` de PHP ou SMTP, et le mot de passe SMTP) reste dans `includes/mail-config.php`, car un mot de passe n'a rien à faire dans un formulaire web ; essayez-la dans System check → Mail check. L'adresse affichée **sur le site** (pied de page et page Contact) est un autre réglage, dans Header & footer.

#### Utilisateurs et mots de passe

- **`/admin/users.php` :** ajouter, modifier ou supprimer des comptes administrateur. Le dernier compte ne peut jamais être supprimé : vous ne pouvez pas vous enfermer dehors. **My account** (menu du haut) modifie vos propres informations. Laissez le mot de passe vide pour le conserver.
- **Mot de passe oublié :** `/admin/forgot-password.php` envoie un lien valable **60 minutes**, utilisable une seule fois. La page affiche toujours le même message, que l'adresse existe ou non.
- Les e-mails de réinitialisation utilisent le même système d'envoi que le formulaire de contact, et le lien est construit à partir de `SITE_URL` + `/admin`.

#### Conversion en blocs (une seule fois)

`/admin/migrate-blocks.php` transforme l'ancien contenu de l'Accueil et du Produit en blocs. Il lit votre contenu actuel, sauvegarde chaque fichier au préalable, laisse les anciennes clés intactes et refuse de s'exécuter deux fois. Tant qu'il n'a pas été lancé, les pages publiques Accueil et Produit sont vides.

#### Contrôle système

`/admin/system-check.php` comporte deux parties.

- **Mail check :** envoie un vrai e-mail de test (avec `mail()` de PHP ou avec les réglages SMTP que vous saisissez), affiche toute la conversation avec le serveur de messagerie, teste si le serveur peut l'atteindre, examine les enregistrements SPF et DMARC de votre domaine, explique toute erreur en langage clair, et vous donne le `mail-config.php` à utiliser.
- **File & SQLite check :** essaie 15 opérations sur les fichiers dans chaque dossier, réécrit les fichiers existants avec eux-mêmes (sans changement), teste votre vraie base et de nouvelles bases de test dans chaque dossier, et explique ce qu'il trouve. Chaque résultat peut être copié en rapport texte à envoyer à votre hébergeur.

#### Cette page d'aide

`/admin/lisez-moi.php` affiche ce document en anglais ou en français. Elle lit `README.md` : modifiez le fichier pour modifier la page.

### Formulaire de contact et e-mail

1. Le visiteur remplit nom, e-mail, message (et éventuellement téléphone et sujet). Un champ caché piège les robots de spam simples.
2. Le message est envoyé **depuis votre propre domaine** (les serveurs de messagerie rejettent les messages prétendant venir de l'adresse d'un visiteur). L'adresse du visiteur est en **Reply-To** : « Répondre » lui répond directement.
3. Le **destinataire** et l'**expéditeur** se règlent dans **Email settings**. Tant qu'ils ne sont pas enregistrés, le destinataire est `SITE_EMAIL` dans `includes/config.php` et l'expéditeur est automatique.
4. Si l'envoi échoue, le visiteur voit une erreur qui mentionne votre adresse e-mail, et la raison est écrite dans le journal d'erreurs du serveur.

**La façon d'envoyer** se règle dans `includes/mail-config.php` (copiez `includes/mail-config.sample.php`) :

- `transport` `mail` : le `mail()` de PHP. Simple, mais il indique seulement que le serveur a accepté le message, pas qu'il a été distribué.
- `transport` `smtp` : un vrai serveur de messagerie (celui de l'hébergeur, ou Brevo, Mailjet, Gmail, …). Fiable, et les erreurs sont expliquées.
- `from_address` : l'expéditeur par défaut, utilisé quand aucun n'est enregistré dans Email settings (qui l'emporte). Doit être une vraie boîte de votre domaine. Vide = automatique (l'identifiant SMTP s'il s'agit d'une adresse, sinon `contact@votre-domaine`).

Utilisez **Admin → System check → Mail check** pour trouver des réglages qui fonctionnent avant de les noter. Pour une bonne distribution, votre domaine doit avoir des enregistrements SPF (et idéalement DKIM et DMARC) pour le service qui envoie le courrier.

### Sauvegardes et restauration

Chaque enregistrement copie d'abord le fichier précédent dans `data/backups/` :

- `en-20261018-143000.json` (une série par langue), `structure-….json`, `site-….json`.
- Les 30 plus récentes de chaque série sont conservées ; les plus anciennes sont supprimées automatiquement.

**Pour restaurer :** copiez la sauvegarde par-dessus le fichier actuel dans `content/` (en le renommant `en.json`, `structure.json`, …) par FTP ou SSH. Il n'y a pas de bouton de restauration. Téléchargez régulièrement `content/` et `data/` : ils contiennent tout votre contenu et vos comptes.

### Sécurité

En place :

- Les mots de passe sont stockés hachés (bcrypt). Le temps de réponse à la connexion ne révèle pas quels comptes existent.
- Protection CSRF sur chaque formulaire et chaque enregistrement ; le cookie de session est `HttpOnly` et limité à `/admin/`.
- Les jetons de réinitialisation ne sont stockés que hachés, expirent après 60 minutes et ne servent qu'une fois.
- Les fichiers envoyés sont vérifiés d'après leur contenu réel, puis ré-encodés (ce qui supprime les données cachées).
- Le texte saisi dans l'administration est échappé à l'affichage ; seule une courte liste sûre d'entités et de balises est autorisée.
- Les pages d'administration sont en `noindex`. Les dossiers `content/`, `data/`, `includes/` et les fichiers `.md` sont bloqués depuis le web.

À faire sur un site en ligne :

- **Utilisez HTTPS** et, dans `admin/includes/auth.php`, activez la ligne `'secure' => true` du cookie de session.
- **Limitez l'accès à `/admin/`** si possible (par exemple par adresse IP chez l'hébergeur).
- Il n'y a **pas encore de limite de tentatives de connexion** ni de CAPTCHA sur le formulaire de contact. Utilisez des mots de passe robustes.
- Gardez des sauvegardes de `content/` et `data/`.

### Mettre le site à jour

1. Téléchargez `content/`, `data/` et `includes/mail-config.php` comme sauvegarde.
2. Envoyez les nouveaux fichiers **par-dessus** les anciens. N'écrasez jamais `content/`, `data/`, `assets/img/` ni `includes/mail-config.php` sauf si c'est voulu.
3. Vérifiez `includes/config.php` : ne le remplacez pas par une version dont `SITE_URL` / `SITE_EMAIL` diffèrent.
4. Ouvrez le Contrôle système et relancez les tests.

### Dépannage

| Problème | Cause probable et solution |
|---|---|
| Les pages Accueil / Produit n'affichent que l'en-tête et le pied de page | Le site n'est pas encore converti en blocs : **Convert now** sur le tableau de bord (ou `/admin/migrate-blocks.php`) |
| « Permission denied » à l'enregistrement ou à l'envoi d'une image | PHP ne peut pas écrire à cet endroit : lancez **System check → File & SQLite check**. Il indique le dossier exact, les droits et quoi faire |
| Les droits sont déjà 777 et cela échoue encore | L'hébergeur bloque l'écriture pour PHP (protection en écriture ou bac à sable) : vérifiez le panneau d'hébergement ou contactez le support, avec le rapport du Contrôle système |
| `/product` donne une erreur 404 ou une page d'erreur du serveur | `mod_rewrite` / `.htaccess` n'est pas actif : demandez à l'hébergeur d'activer `AllowOverride` |
| Les messages de contact n'arrivent pas | Vérifiez le destinataire dans **Email settings** et cliquez sur **Send a test email**. Puis vérifiez les courriers indésirables, lancez **Mail check**, et essayez le SMTP |
| L'e-mail de réinitialisation n'arrive pas | Même système d'envoi : utilisez **Mail check**. Le lien utilise `SITE_URL` + `/admin` |
| Des visiteurs sont envoyés dans la mauvaise langue | Leur cookie `enwires_lang` ou la langue du navigateur décide ; ils peuvent changer avec le menu des langues |
| « Someone else saved this page » | Un autre administrateur a enregistré avant vous : rechargez la page, puis refaites votre modification |
| Une image ne s'affiche pas | Vérifiez que le fichier existe dans `assets/img/` et que son nom est correct |
| Erreur 413 / d'envoi | Le fichier dépasse la limite du serveur : réduisez l'image (le Contrôle système indique `upload_max_filesize`) |

### Pour les développeurs

**Plan du code**

```
router.php            URL propres, préfixes de langue, 404
.htaccess             envoie les requêtes à router.php, bloque les dossiers privés
index.php  product.php  contact.php  404.php     modèles de pages
includes/config.php   constantes du site, langue (modifier SITE_URL, SITE_EMAIL ici)
includes/header.php  footer.php    en-tête / pied de page (lisent les réglages du layout)
includes/blocks/*.php un modèle par type de bloc
includes/block-registry.php   types de blocs et leurs champs (source unique de vérité)
includes/blocks-core.php      données des blocs : lecture, découpage, repli anglais, valeurs par défaut
includes/layout-core.php      données de l'en-tête et du pied de page
includes/mailer.php           envoi d'e-mails (mail() ou SMTP)
includes/contact-mailer.php   construit l'e-mail du formulaire de contact
admin/builder.php  preview.php  layout.php  email-settings.php  users.php  system-check.php ...
admin/includes/*-store.php    enregistrement avec sauvegardes et contrôle de conflit
```

**Ajouter un type de bloc.** Ajoutez une entrée dans `includes/block-registry.php` (libellé, champs), puis créez `includes/blocks/<type>.php` pour l'afficher. L'éditeur le propose automatiquement. Types de champs : `text`, `textarea`, `select`, `link`, `checkbox`, `image`, `gallery`, `repeater`.

**Ajouter un champ au formulaire de contact.** Ajoutez le champ texte à l'entrée `contact-form` de `includes/block-registry.php`, affichez-le dans `includes/blocks/contact-form.php`, et lisez-le dans `contact.php`. L'état du formulaire (erreurs, envoyé) est transmis au bloc dans `$GLOBALS['enw_contact']`.

**Ajouter une page.** Ajoutez-la dans `enw_site_pages()` de `includes/block-registry.php`, créez son modèle, et ajoutez son slug à `$r_pages` dans `router.php`. Pour une page à blocs, ajoutez-la aussi à `enw_block_pages()`.

**Ajouter une langue.** Cela touche plusieurs listes : `$GLOBALS['LANGUAGES']` et la vérification dans `includes/config.php` ; les listes de langues de `includes/blocks-core.php`, `admin/includes/content-store.php` et `admin/includes/blocks-store.php` ; les trois règles de `.htaccess` et les motifs de `router.php` ; les tableaux `$languages` des pages d'administration ; et un nouveau `content/xx.json` (copie de `en.json`).

**Passer à une base de données plus tard.** La lecture et l'écriture passent par quelques fonctions : `enw_lang_content()`, `enw_structure_load()`, `enw_site_load()` et les fonctions d'enregistrement `cs_*`, `bs_*`, `ls_*`. Remplacez-les, pas les modèles.

**Nommage.** `enw_*` site public, `cs_*` stockage du contenu, `bs_*` stockage des blocs, `ls_*` stockage du layout, `sc_*` contrôle système.

### Limites connues

- Pas de brouillon / publication : un enregistrement est publié immédiatement.
- Pas de bouton de restauration des sauvegardes (copie manuelle).
- Les images envoyées ne sont ni redimensionnées ni supprimées quand elles ne servent plus ; l'information de rotation de l'appareil photo est perdue au ré-encodage.
- Pas de limite de tentatives de connexion, pas de CAPTCHA.
- Le contenu est dans des fichiers JSON : enregistrer demande un accès en écriture ; chaque enregistrement réécrit un fichier entier (un contrôle de conflit évite d'écraser le travail d'un autre).
- Le comportement du formulaire de contact (champs, contrôles, piège à spam) est fixé dans le code ; seuls ses textes et sa mise en page sont modifiables.
- Pas encore de génération de `sitemap.xml` / `robots.txt`.
