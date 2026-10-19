<?php
// Lisez-moi / Read me — the documentation page of the back-office.
//
// It shows /README.md (the same file developers read) in English or French.
// The file holds both languages, separated by <!-- lang:en --> and <!-- lang:fr -->
// markers; this page splits them and the browser renders the chosen one
// (admin/assets/docs.js). To change the documentation, edit README.md.

require_once __DIR__ . '/includes/auth.php';
admin_require_login();

$readme = dirname(__DIR__) . '/README.md';
$md = is_file($readme) ? (string) file_get_contents($readme) : '';

$parts = ['en' => '', 'fr' => ''];
$tok = preg_split('/<!--\s*lang:(en|fr)\s*-->/', $md, -1, PREG_SPLIT_DELIM_CAPTURE);
for ($i = 1; $i + 1 < count($tok); $i += 2) {
    // Drop the "## English" / "## Français" heading line: this page has its own language switch.
    $parts[$tok[$i]] = preg_replace('/^\s*##\s+[^\n]*\n/', '', $tok[$i + 1], 1);
}
$hasDoc = trim($parts['en']) !== '' || trim($parts['fr']) !== '';

$requested = $_GET['lang'] ?? '';
$requested = in_array($requested, ['en', 'fr'], true) ? $requested : '';

$json = json_encode(
    ['en' => $parts['en'], 'fr' => $parts['fr'], 'requested' => $requested],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
);
if ($json === false) {
    $json = '{"en":"","fr":"","requested":""}';
}

$pageTitle = 'Lisez-moi / Help';
require __DIR__ . '/includes/layout-header.php';
?>
<style>
  body .admin-main { max-width: 1180px; }
  .doc-top { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
  .doc-top h1 { margin-bottom: 4px; }
  .doc-actions { display: flex; gap: 10px; align-items: center; }
  .doc-lang { display: flex; border: 1px solid var(--line); border-radius: 5px; overflow: hidden; }
  .doc-lang button { border: 0; background: #fff; padding: 8px 16px; font: inherit; cursor: pointer; color: var(--ink-dim); }
  .doc-lang button + button { border-left: 1px solid var(--line); }
  .doc-lang button.is-active { background: var(--ink); color: #fff; }

  .doc-layout { display: grid; grid-template-columns: 250px minmax(0, 1fr); gap: 28px; align-items: start; }
  .doc-toc { position: sticky; top: 16px; max-height: calc(100vh - 32px); overflow: auto; font-size: .88rem; border-right: 1px solid var(--line); padding-right: 14px; }
  .doc-toc strong { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: var(--ink-dim); margin-bottom: 8px; }
  .doc-toc a { display: block; padding: 4px 8px; border-left: 3px solid transparent; color: var(--ink); text-decoration: none; border-radius: 0 4px 4px 0; }
  .doc-toc a:hover { background: #f1f1f4; }
  .doc-toc a.is-active { border-left-color: var(--red); background: #f6f6f8; font-weight: 600; }
  .doc-toc a.sub { padding-left: 22px; font-size: .84rem; color: var(--ink-dim); }

  .doc-body { background: var(--panel); border: 1px solid var(--line); border-radius: 8px; padding: 10px 32px 34px; line-height: 1.65; min-width: 0; }
  .doc-body h2 { margin: 38px 0 10px; padding-bottom: 8px; border-bottom: 2px solid var(--line); font-size: 1.45rem; scroll-margin-top: 14px; }
  .doc-body h2:first-child { margin-top: 24px; }
  .doc-body h3 { margin: 26px 0 8px; font-size: 1.1rem; scroll-margin-top: 14px; }
  .doc-body p { margin: 10px 0; }
  .doc-body ul, .doc-body ol { margin: 8px 0 12px; padding-left: 24px; }
  .doc-body li { margin: 4px 0; }
  .doc-body code { background: #f1f1f4; padding: 1px 6px; border-radius: 4px; font-size: .88em; font-family: ui-monospace, Menlo, Consolas, monospace; }
  .doc-body pre.doc-code { background: #1e1e24; color: #e6e6ea; padding: 14px 16px; border-radius: 6px; overflow: auto; font-size: .84rem; line-height: 1.5; }
  .doc-body pre.doc-code code { background: none; padding: 0; color: inherit; font-size: inherit; }
  .doc-body .doc-table { overflow-x: auto; margin: 12px 0 18px; }
  .doc-body table { border-collapse: collapse; width: 100%; font-size: .92rem; }
  .doc-body th { text-align: left; background: #f3f3f6; }
  .doc-body th, .doc-body td { border: 1px solid var(--line); padding: 8px 12px; vertical-align: top; }
  .doc-body tbody tr:nth-child(even) td { background: #fafafb; }
  .doc-body blockquote { margin: 12px 0; padding: 8px 16px; border-left: 4px solid var(--red); background: #fafafb; }
  .doc-body a { color: var(--red); }

  @media (max-width: 860px) {
    .doc-layout { display: block; }
    .doc-toc { position: static; max-height: none; border-right: 0; border-bottom: 1px solid var(--line); padding: 0 0 12px; margin-bottom: 16px; }
    .doc-body { padding: 4px 16px 24px; }
  }
  @media print {
    .admin-header, .doc-toc, .doc-actions { display: none !important; }
    .doc-layout { display: block; }
    .doc-body { border: 0; padding: 0; }
    .doc-body pre.doc-code { white-space: pre-wrap; background: #f3f3f6; color: #111; }
  }
</style>

<div class="doc-top">
  <div>
    <h1>Lisez-moi <span style="font-weight:400; color:var(--ink-dim);">/ Read me</span></h1>
    <p class="subtitle" id="doc-sub">How the website and the back-office work.</p>
  </div>
  <div class="doc-actions">
    <div class="doc-lang" role="group" aria-label="Language / Langue">
      <button type="button" data-lang="en">English</button>
      <button type="button" data-lang="fr">Français</button>
    </div>
    <button type="button" class="btn-secondary" id="doc-print">Print / Imprimer</button>
  </div>
</div>

<?php if (!$hasDoc): ?>
  <div class="alert alert-error">
    README.md was not found or has no content (looked for <code><?php echo htmlspecialchars($readme); ?></code>).<br>
    README.md est introuvable ou vide.
  </div>
<?php else: ?>
  <div class="doc-layout">
    <nav class="doc-toc" id="doc-toc" aria-label="Contents"></nav>
    <article class="doc-body" id="doc-body"></article>
  </div>
  <noscript>
    <pre style="white-space:pre-wrap;"><?php echo htmlspecialchars($md); ?></pre>
  </noscript>
<?php endif; ?>

<script type="application/json" id="doc-data"><?php echo $json; ?></script>
<script src="assets/docs.js"></script>
<script>
(function () {
  var dataEl = document.getElementById('doc-data');
  var body = document.getElementById('doc-body');
  var tocEl = document.getElementById('doc-toc');
  if (!dataEl || !body || !window.EnwDocs) return;

  var data = JSON.parse(dataEl.textContent);
  var UI = {
    en: { sub: 'How the website and the back-office work.', toc: 'Contents' },
    fr: { sub: 'Comment fonctionnent le site web et le back-office.', toc: 'Sommaire' }
  };
  var observer = null;

  function pickInitial() {
    if (data.requested === 'fr' || data.requested === 'en') return data.requested;
    try {
      var saved = localStorage.getItem('enw_doc_lang');
      if (saved === 'fr' || saved === 'en') return saved;
    } catch (e) { /* storage unavailable */ }
    return /^fr/i.test(navigator.language || '') ? 'fr' : 'en';
  }

  function show(lang) {
    var res = EnwDocs.render(data[lang] || '', { shift: 1, tocMax: 3 });
    body.innerHTML = res.html;
    body.setAttribute('lang', lang);

    var html = '<strong>' + UI[lang].toc + '</strong>';
    res.toc.forEach(function (t) {
      html += '<a href="#' + t.id + '"' + (t.level > 2 ? ' class="sub"' : '') + '>' + EnwDocs.inline(t.text) + '</a>';
    });
    tocEl.innerHTML = html;

    document.getElementById('doc-sub').textContent = UI[lang].sub;
    document.documentElement.setAttribute('lang', lang);
    Array.prototype.forEach.call(document.querySelectorAll('[data-lang]'), function (b) {
      b.classList.toggle('is-active', b.getAttribute('data-lang') === lang);
    });
    try { localStorage.setItem('enw_doc_lang', lang); } catch (e) { /* ignore */ }
    if (window.history && history.replaceState) {
      history.replaceState(null, '', location.pathname + '?lang=' + lang + location.hash);
    }

    // highlight the section being read
    if (observer) observer.disconnect();
    if ('IntersectionObserver' in window) {
      var links = {};
      Array.prototype.forEach.call(tocEl.querySelectorAll('a'), function (a) { links[a.getAttribute('href').slice(1)] = a; });
      observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (!e.isIntersecting || !links[e.target.id]) return;
          Object.keys(links).forEach(function (k) { links[k].classList.remove('is-active'); });
          links[e.target.id].classList.add('is-active');
        });
      }, { rootMargin: '0px 0px -70% 0px' });
      Array.prototype.forEach.call(body.querySelectorAll('h2[id], h3[id]'), function (h) { observer.observe(h); });
    }
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-lang]'), function (b) {
    b.addEventListener('click', function () { show(b.getAttribute('data-lang')); window.scrollTo(0, 0); });
  });
  document.getElementById('doc-print').addEventListener('click', function () { window.print(); });

  show(pickInitial());
  if (location.hash) {
    try {
      var target = document.getElementById(decodeURIComponent(location.hash.slice(1)));
      if (target) target.scrollIntoView();
    } catch (e) { /* malformed #hash: ignore */ }
  }
})();
</script>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
