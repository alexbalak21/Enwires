# Enwires CMS implementation plan

## 1. Goal

Evolve Enwires from its current site-specific JSON block editor into a complete,
lightweight CMS for PHP 8.4 with:

- a clean-URL router and dynamic page management;
- a developer/admin block registry and a page builder for using those blocks;
- Bootstrap as the first presentation renderer;
- live block and page previews;
- draft, publish, versioning, and recovery workflows;
- a compiler that produces static HTML pages from published content;
- multilingual content, media management, SEO, permissions, and safe deployment.

The implementation must preserve the project's current strengths:

- no framework and no mandatory Composer dependency;
- shared-hosting compatibility;
- human-readable JSON content;
- existing English, French, Chinese, and Japanese support;
- the current admin authentication and SQLite account database;
- safe output escaping and server-side validation.

This is an incremental migration, not a rewrite. The existing public pages,
router, block registry, preview endpoint, and JSON files should continue to work
at every intermediate milestone.

---

## 2. Current baseline

The repository already provides a useful foundation:

- `router.php` dispatches `/`, `/product`, `/contact`, language-prefixed URLs,
  redirects legacy `.php` URLs, and renders a real 404 response.
- `includes/block-registry.php` defines registered block types and fields.
- `includes/blocks-core.php` resolves shared structure plus translated fields.
- `includes/blocks.php` renders block templates from `includes/blocks/`.
- `admin/builder.php` and `admin/assets/builder.js` provide a block editor with
  an iframe preview.
- `admin/preview.php` renders the real public template using unsaved draft data.
- `admin/includes/blocks-store.php` performs validation, backups, locking, and
  optimistic revision conflict detection.
- `content/structure.json` stores shared block structure and `content/*.json`
  stores translated values.
- `data/admin.sqlite` stores admin accounts; content is currently file-based.
- the architecture guide already defines semantic content/settings separation,
  reusable blocks, draft states, versions, media, and future API concepts.

Important gaps to address:

1. The router has a hardcoded page table, so editors cannot create arbitrary
   pages.
2. The current editor is primarily a page block editor; there is no complete
   developer-facing block-definition workflow.
3. The current renderer emits site-specific HTML/CSS rather than a formal
   renderer contract and Bootstrap mapping layer.
4. Preview is live, but saving is still the primary published read path.
5. There is no publish artifact, compiler manifest, cache invalidation strategy,
   or atomic deployment process.
6. Content writes can update language data and shared structure in separate
   operations; this needs a transaction-like commit boundary for publishing.
7. Page lifecycle, permissions, autosave, versions, media metadata, and
   scheduled publishing are not yet first-class CMS services.

---

## 3. Architectural decisions

### 3.1 Content remains semantic

Persist semantic values such as:

```json
{
  "theme": "dark",
  "container": "wide",
  "spacing": "large"
}
```

Do not persist Bootstrap classes, arbitrary HTML, JavaScript, or CSS snippets in
normal content. The renderer maps semantic values to Bootstrap classes:

```php
$spacingClasses = [
    'none' => '',
    'small' => 'py-2',
    'medium' => 'py-4',
    'large' => 'py-5',
    'xlarge' => 'py-5 py-lg-6',
];
```

This keeps content portable if Bootstrap is later replaced or a headless API
is added.

### 3.2 Bootstrap is a renderer/theme, not the content model

Create a Bootstrap 5 renderer as the first implementation. It owns:

- container width mappings;
- row/column layout mappings;
- spacing and alignment mappings;
- theme/color mappings;
- responsive visibility and ordering rules;
- accessible component markup;
- Bootstrap asset versions and loading strategy.

Use a pinned, locally hosted Bootstrap build for production. Do not make the
CMS depend on an external CDN at runtime. A later renderer can target a custom
theme without migrating content.

### 3.3 One canonical render pipeline

The same pipeline must serve:

1. public dynamic rendering;
2. admin live preview;
3. compiled HTML generation;
4. future API serialization where appropriate.

The pipeline should be:

```text
request/context
  -> load page revision
  -> resolve language and fallback
  -> resolve block definitions
  -> validate/normalize block data
  -> render through selected renderer
  -> produce HTML + metadata + asset dependencies
```

Do not create a separate simplified preview template. The existing preview
endpoint should be adapted to this pipeline so the preview and public output
cannot drift.

### 3.4 Compile published pages, do not compile drafts

The compiler generates artifacts only for a valid published revision. Drafts
remain available through the authenticated preview route and are never exposed
through public compiled files.

Recommended artifact layout:

```text
data/
  compiled/
    manifest.json
    en/
      index.html
      product/index.html
    fr/
      index.html
      product/index.html
```

The exact web-root location depends on deployment constraints. If the document
root cannot safely expose the artifact directory, the router can stream the
artifact after validating the requested page. Prefer server-served static files
when the hosting environment supports safe atomic publication.

### 3.5 Atomic publication

Compilation must write to a temporary release directory, validate all output,
write a manifest, and rename the release into place. A failed build must leave
the previous release intact.

The manifest should include:

- release ID and creation time;
- CMS/schema version;
- renderer/theme version;
- page key, slug, language, and revision;
- output path;
- content hash;
- compiler status and error details.

---

## 4. Target domain model

Use stable IDs and explicit schemas. JSON remains the default content store;
SQLite may later become an optional store for larger installations.

### Site

- site identity and URL;
- default language and enabled languages;
- renderer/theme;
- SEO defaults;
- navigation and footer;
- compiler settings.

### Page

- `id`;
- `slug`;
- route/path and language policy;
- label/title;
- status: `draft`, `published`, `scheduled`, or `archived`;
- `published_revision`;
- current draft revision;
- SEO metadata;
- ordered block references;
- timestamps and author IDs.

### Block instance

- stable instance ID;
- registered block type;
- semantic settings;
- translated content reference;
- optional reusable/global-block reference;
- visibility and responsive options.

### Block definition

- type key and version;
- label, category, icon, and description;
- field schema;
- defaults;
- validation and normalization rules;
- renderer identifier;
- migration handlers for schema upgrades.

### Revision

- immutable snapshot of a page and its block data;
- revision number/hash;
- author and note;
- created time;
- draft/published relationship.

### Media item

- stable ID and storage filename;
- MIME type, dimensions, byte size, checksum;
- generated variants;
- translated alt text/caption;
- upload author and timestamps.

### User/role

Keep the existing account system, then add explicit capabilities:

- manage users;
- manage settings;
- create/edit pages;
- publish pages;
- define blocks;
- manage media;
- compile/deploy.

---

## 5. Implementation phases

Each phase should be merged only after its acceptance criteria pass. Do not
start the compiler before the canonical render contract and page revision model
are stable.

### Phase 0 — Baseline and safety net

**Tasks**

- Confirm PHP 8.4 compatibility with strict error reporting in development.
- Record current routes, languages, block types, content shapes, and output
  snapshots.
- Add a small CLI or test harness that renders every existing page/language.
- Add JSON schema/version metadata where it can be introduced without changing
  existing values.
- Document backup/restore and local development commands.
- Ensure all write operations report failures explicitly and never present a
  success response after a partial write.

**Acceptance criteria**

- Existing pages and legacy redirects behave exactly as today.
- All current block types render in all enabled languages.
- A baseline render snapshot can detect accidental HTML changes.

### Phase 1 — Formalize the domain and storage services

**Tasks**

- Extract storage concerns from page rendering into services/classes or
  consistently named modules:
  `PageRepository`, `BlockRepository`, `RevisionRepository`,
  `TranslationRepository`, `MediaRepository`, and `SettingsRepository`.
- Add a schema version and migration runner for JSON files.
- Replace ad-hoc `file_get_contents`/writes in new code with locked,
  atomic helpers.
- Introduce a commit bundle for changes that touch structure, translations,
  metadata, and revision information together.
- Keep compatibility adapters for the current `structure.json` and language
  files during migration.

**Acceptance criteria**

- Existing builder saves through the new storage boundary.
- A failed multi-file save leaves the previous committed state usable.
- Migrations are repeatable and backed up before changing content.

### Phase 2 — Renderer contract and Bootstrap theme

**Tasks**

- Define a renderer interface that accepts a normalized page view model and
  returns HTML plus asset/dependency metadata.
- Move presentation mappings into a Bootstrap renderer/theme module.
- Add semantic mapping helpers for:
  container, grid columns, gap, spacing, alignment, theme, buttons,
  typography, and responsive behavior.
- Refactor existing block templates to use the renderer helpers instead of
  hardcoded site-specific layout decisions where practical.
- Pin and locally build Bootstrap assets; document the selected version.
- Add accessible markup requirements: headings, labels, focus states,
  alt text, keyboard navigation, and reduced-motion behavior.

**Acceptance criteria**

- Existing content renders using Bootstrap classes without storing those
  classes in JSON.
- Invalid semantic options fall back to defined safe defaults and are logged.
- Desktop, tablet, and mobile output is tested for every core block.

### Phase 3 — Block Builder / block-definition system

The term “Block Builder” must be explicit in the product because it has two
different meanings:

- **Block Builder:** developers/admins define reusable block types.
- **Page Builder:** editors assemble registered block instances into pages.

**Tasks**

- Extend `block-registry.php` into a versioned block definition registry.
- Support field types: text, textarea, rich text with a strict allow-list,
  image, gallery, link, select, color, checkbox, repeater, and optional
  nested blocks.
- Add categories, descriptions, icons, defaults, validation, conditional
  fields, and schema versioning.
- Add a developer-facing definition workflow only if definitions are intended
  to be editable at runtime; otherwise keep definitions in PHP and provide an
  admin preview/catalog.
- Generate editor controls from the same schema used for server validation.
- Add block migrations for renamed fields and changed defaults.
- Require every block type to declare a renderer and preview-safe behavior.

**Acceptance criteria**

- A new block can be registered with one definition and one renderer.
- The editor never trusts client field types or client validation.
- Unknown block types render a visible, logged placeholder in admin and a safe
  non-breaking fallback on public pages.

### Phase 4 — Page Builder and live preview

**Tasks**

- Generalize the existing builder from the fixed page list to dynamic pages.
- Support create, edit, duplicate, rename, slug editing, archive, and delete.
- Support drag/drop reorder, insert, duplicate, remove, and configure blocks.
- Keep the split interface: editor below/alongside the live block preview as
  requested, with responsive desktop/tablet/mobile preview modes.
- Preserve the current iframe preview protocol and block selection messages.
- Add a page-level preview route that renders the full header, navigation,
  blocks, footer, SEO head, and forms in one canonical pipeline.
- Add debounced draft preview requests with cancellation/request IDs so an old
  response cannot overwrite a newer draft.
- Show validation errors next to fields and a clear global preview error.
- Add preview links for unsaved drafts that are signed, short-lived, and
  read-only.

**Acceptance criteria**

- Typing or changing a block updates the visible page without saving.
- Selecting a block in the preview focuses the corresponding editor card.
- Preview output matches public output for the same committed revision.
- A slow or failed preview request does not corrupt the editor state.

### Phase 5 — Dynamic router and page lifecycle

**Tasks**

- Replace the hardcoded `$r_pages` table with a route resolver backed by the
  page repository, while retaining reserved system routes.
- Normalize language prefixes, slashes, case, and legacy `.php` redirects.
- Define route precedence:
  system/admin/assets, language prefix, published page slug, 404.
- Prevent page slugs from colliding with `admin`, `assets`, `data`, `includes`,
  compiler paths, language prefixes, or reserved endpoints.
- Add canonical URLs, language alternates, 301 redirects for changed slugs,
  and a redirect history.
- Make unpublished pages return 404 publicly while remaining available in
  authenticated preview.
- Preserve the real 404 status and localized 404 page.

**Acceptance criteria**

- Editors can create a page and publish it at a clean URL without PHP code.
- Existing `/`, `/product`, `/contact`, language routes, and legacy redirects
  remain compatible.
- Draft and archived pages are never accidentally served publicly.

### Phase 6 — Drafts, publishing, versions, and autosave

**Tasks**

- Implement immutable revisions and explicit current draft/published pointers.
- Add Save Draft, Publish, Unpublish, Schedule, Archive, and Restore actions.
- Record author, timestamp, change note, and source of each revision.
- Add autosave storage separate from the canonical draft; autosave must never
  publish or alter the public revision.
- Add optimistic locking and a conflict UI for simultaneous editors.
- Add version browser, diff view, preview version, and restore with a new
  revision (never destructive overwrite).
- Add scheduled publishing via a CLI cron entry or authenticated task endpoint.

**Acceptance criteria**

- Public requests always resolve to the published revision.
- Restore and rollback are auditable and reversible.
- Concurrent saves produce a clear conflict instead of silent overwrites.

### Phase 7 — Compiler and compiled delivery

**Tasks**

- Implement a `Compiler` service that accepts a release target and page
  selection.
- Compile every published page/language using the canonical renderer.
- Generate HTML, metadata, asset references, a manifest, and a build report.
- Validate generated HTML enough to catch missing templates, malformed
  renderer output, missing required metadata, and broken internal links.
- Write to a temporary release directory and atomically activate only after
  all required pages pass.
- Invalidate/rebuild only affected pages for normal publishing; provide a full
  rebuild command for renderer/theme changes.
- Add compiler commands such as:

  ```text
  php bin/cms compile
  php bin/cms compile --page=home --lang=fr
  php bin/cms publish
  php bin/cms verify
  ```

- Add a deployment mode:
  - static artifact mode for hosts that serve the compiled directory;
  - router fallback mode when artifacts are unavailable;
  - never silently serve stale output after a failed compile.
- Add cache headers and a release ID so old assets/artifacts can be purged
  safely.

**Acceptance criteria**

- A published page can be served from compiled HTML without executing its
  block rendering path on every request.
- A compiler failure leaves the prior release live.
- The manifest identifies exactly which content and renderer versions produced
  each file.
- Preview and compiled output are equivalent for the same revision.

### Phase 8 — Media library and asset pipeline

**Tasks**

- Create media records with MIME, dimensions, checksum, size, and ownership.
- Validate uploads by content, not only filename or browser MIME.
- Generate safe filenames and optional responsive image variants.
- Add alt text/caption per language and a media picker in the page builder.
- Track references so unused media can be reviewed before deletion.
- Prevent executable uploads and serve user uploads with safe content headers.
- Keep existing `assets/img` files discoverable during migration.

**Acceptance criteria**

- Unsupported, oversized, malformed, or executable files are rejected.
- Existing image references remain valid after migration.
- The compiler includes only required media/asset references where configured.

### Phase 9 — CMS administration, permissions, and APIs

**Tasks**

- Add dashboard areas for pages, block catalog, media, revisions, redirects,
  compiler status, settings, and system health.
- Apply capability checks to every page, save, upload, publish, restore, and
  compile endpoint.
- Add audit events for authentication, content changes, publication, restores,
  uploads, and failed actions.
- Add a versioned read API after the internal domain model is stable:
  `/api/v1/pages`, `/api/v1/pages/{id}`, `/api/v1/blocks/{id}`.
- Keep API output semantic; do not make Bootstrap HTML the only API format.
- Add rate limits and CSRF protection for state-changing admin/API requests.

**Acceptance criteria**

- An editor can edit but cannot publish unless granted that capability.
- Every mutation is attributable to a user and revision.
- API responses are versioned and do not expose private filesystem paths.

### Phase 10 — Migration, deployment, and documentation

**Tasks**

- Add a migration command from the current `structure.json` plus language
  files into the new page/revision model.
- Back up all content before migration and provide a dry-run report.
- Keep compatibility reads until all existing pages are migrated and verified.
- Update `README.md` with PHP 8.4, Bootstrap build, compiler, cron, writable
  directories, deployment, rollback, and troubleshooting instructions.
- Update the architecture guide to document the implemented contracts rather
  than only the target design.
- Document Apache and Nginx routing differences and compiled artifact serving.

**Acceptance criteria**

- A fresh installation and an upgraded existing installation both work.
- A release can be rolled back to the previous compiled artifact and content
  backup without manual JSON surgery.
- Documentation covers local development and production deployment.

---

## 6. Suggested implementation order inside the codebase

Prefer these additions/refactors in this order:

```text
includes/
  Cms/
    Contract/
    Domain/
    Repository/
    Renderer/
    Compiler/
    Validation/
    Migration/
  blocks/
  block-registry.php          # compatibility facade initially
  blocks-core.php             # compatibility facade initially
  blocks.php                  # delegates to renderer pipeline

admin/
  pages.php
  page-save.php
  page-preview.php
  revisions.php
  media.php
  compiler.php
  api/

bin/
  cms                         # CLI entry point

data/
  compiled/
  releases/
  versions/
  autosaves/
```

Do not move every existing file at once. Introduce the new services behind
compatibility functions, migrate one path at a time, and delete compatibility
code only after the migration and regression tests pass.

---

## 7. Validation and test strategy

### Automated tests

Add focused tests for:

- route normalization, language detection, reserved slugs, redirects, and 404;
- page/revision status visibility;
- shared settings versus translated content;
- field normalization and all field types;
- invalid block types and invalid semantic options;
- escaping, rich-text allow-list behavior, and URL validation;
- optimistic locking and concurrent writes;
- migration idempotency;
- compiler atomicity and failed-build rollback;
- manifest hashes and incremental rebuild selection.

### Render tests

For every core block and every supported language:

- render the saved public page;
- render the authenticated draft preview;
- compile the published revision;
- compare normalized output and expected asset dependencies.

### Browser tests

Cover:

- add, remove, duplicate, reorder, and edit blocks;
- responsive preview modes;
- block selection from preview to editor and back;
- validation and save errors;
- conflict handling;
- draft preview and publish;
- media selection/upload;
- page creation and slug changes.

### Security tests

Verify:

- CSRF on every state-changing admin request;
- authentication and capability checks;
- no path traversal in slugs, media, compiler paths, or filenames;
- no executable upload;
- no stored XSS through text, rich text, attributes, or SVG;
- no draft leakage through router, compiler, cache, or preview URLs;
- safe error responses that do not reveal filesystem or credential data.

### Operational tests

- PHP 8.4 with required extensions;
- Apache rewrite rules;
- PHP built-in server with `router.php`;
- Nginx equivalent configuration;
- unwritable content/data/compiler directories;
- interrupted compile and rollback;
- missing Bootstrap assets;
- large media and large page limits.

---

## 8. Non-functional requirements

- PHP 8.4; use strict types in new modules where compatible with existing code.
- No runtime Composer requirement unless a concrete dependency justifies it.
- Atomic writes and backups for every content/revision mutation.
- Public requests must not expose private JSON, SQLite, source templates, or
  compiler staging directories.
- All user content is escaped by default; rich text is an explicit, narrow
  allow-list feature.
- Published content is immutable; edits create a new draft/revision.
- Compiler output is reproducible from revision + renderer/theme versions.
- Errors are explicit in logs and admin UI; never hide a partial save or build.
- Define limits for blocks per page, nested depth, field length, upload size,
  and compile duration.
- Keep a rollback path at both content and compiled-release levels.

---

## 9. Milestone definition of done

### Milestone A — Bootstrap-compatible foundation

- Existing pages render through a renderer contract.
- Bootstrap classes are generated from semantic settings.
- Current block builder and preview still work.

### Milestone B — Dynamic CMS editing

- Editors can create pages and assemble registered blocks.
- Router resolves published dynamic pages.
- Draft/publish status and permissions are enforced.

### Milestone C — Production publishing

- Revisions, autosave, rollback, media metadata, and audit events work.
- Compiler creates atomic, verified releases.
- Public delivery uses compiled HTML with safe router fallback.

### Milestone D — Extensible platform

- Versioned block-definition system and migrations are documented.
- API v1 exposes semantic page data.
- New renderers can be added without changing stored content.

---

## 10. Immediate next sprint

Start with a vertical slice instead of implementing every CMS feature at once:

1. Add renderer interfaces and a Bootstrap theme mapping layer.
2. Route the existing block templates through that renderer.
3. Add a normalized page view model and revision-aware read path.
4. Adapt the existing preview endpoint to the canonical pipeline.
5. Add a minimal compiler for one published page and one language.
6. Verify preview, public dynamic rendering, and compiled output match.
7. Only then generalize the router to editor-created pages.

This sequence validates the highest-risk requirement—the relationship between
Bootstrap rendering, live preview, and compiled output—before investing in the
larger page-management and workflow surfaces.
