<?php
// Live-preview endpoint for the block builder.
//
//   GET  preview.php?page=home&lang=en   -> the page as currently saved
//   POST preview.php?page=home&lang=en   -> the page rendered from an UNSAVED
//                                           draft (JSON body: {"blocks": [...]})
//
// It renders the real public template (index.php / product.php), so the
// preview is identical to what visitors get — no second set of templates.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/blocks-store.php';
require_once dirname(__DIR__) . '/includes/layout-core.php';

if (!admin_is_logged_in()) {
    http_response_code(401);
    exit('Logged out.');
}

$sitePages = enw_site_pages();
$page = $_GET['page'] ?? '';
$lang = $_GET['lang'] ?? '';
if (!isset($sitePages[$page]) || !cs_valid_lang($lang)) {
    http_response_code(400);
    exit('Bad request.');
}

define('ENWIRES_PREVIEW', true);
define('ENWIRES_ROUTED', true);
define('ENWIRES_PAGE_KEY', $page);
define('ENWIRES_PAGE_FILE', $sitePages[$page]['file']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['layout_json'])) {
        // Header / footer editor: a form post carrying the unsaved layout draft.
        if (!admin_verify_csrf()) {
            http_response_code(403);
            exit('Bad token.');
        }
        $draft = json_decode((string) $_POST['layout_json'], true);
        if (!is_array($draft)) {
            http_response_code(400);
            exit('Bad draft.');
        }
        $GLOBALS['__enw_preview_layout'] = enw_layout_draft_to_layout($draft, $lang);
    } else {
        // Block builder: a JSON body carrying the unsaved blocks.
        if (!bs_verify_csrf_header()) {
            http_response_code(403);
            exit('Bad token.');
        }
        $body = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($body) || !isset($body['blocks']) || !is_array($body['blocks']) || !isset(enw_block_pages()[$page])) {
            http_response_code(400);
            exit('Bad draft.');
        }
        $GLOBALS['__enw_preview_blocks'][$page] = enw_draft_to_blocks($body['blocks'], $page, $lang);
    }
}

// A preview must never act like a real submission (e.g. send the contact form):
// the page below is always rendered as a plain GET with no form data.
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';

// The public page reads $lang (a global) to pick the language.
ob_start();
require dirname(__DIR__) . '/' . ENWIRES_PAGE_FILE;
$html = ob_get_clean();

// Preview-only extras: highlight styles, click-to-select, and a content hash
// per block so the editor can swap only the blocks that changed.
$head = '<style>'
    . '[data-block-id]{cursor:pointer}'
    . '.enw-hover{outline:3px solid #FF1400;outline-offset:-3px}'
    . '.enw-selected{box-shadow:inset 0 0 0 3px #4da3ff}'
    . '</style>';
$tail = <<<'JS'
<script>
(function () {
  function hash(str) { var h = 5381, i = str.length; while (i) { h = (h * 33) ^ str.charCodeAt(--i); } return String(h >>> 0); }
  document.querySelectorAll('[data-enw-blocks] > [data-block-id]').forEach(function (el) {
    el.setAttribute('data-enw-h', hash(el.outerHTML));
  });
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a'); if (a) e.preventDefault();
    var blk = e.target.closest('[data-block-id]');
    if (blk) parent.postMessage({ enw: 'select', id: blk.getAttribute('data-block-id') }, location.origin);
  }, true);
  document.addEventListener('submit', function (e) { e.preventDefault(); }, true);
  window.addEventListener('message', function (e) {
    if (e.origin !== location.origin || !e.data || !e.data.enw) return;
    var el = e.data.id ? document.querySelector('[data-block-id="' + e.data.id + '"]') : null;
    document.querySelectorAll('.enw-hover').forEach(function (n) { n.classList.remove('enw-hover'); });
    if (e.data.enw === 'hover' && el) el.classList.add('enw-hover');
    if ((e.data.enw === 'scrollto' || e.data.enw === 'mark') && el) {
      document.querySelectorAll('.enw-selected').forEach(function (n) { n.classList.remove('enw-selected'); });
      el.classList.add('enw-selected');
      if (e.data.enw === 'scrollto') el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
})();
</script>
JS;
$html = preg_replace_callback('~</head>~i', function () use ($head) { return $head . '</head>'; }, $html, 1);
$html = preg_replace_callback('~</body>~i', function () use ($tail) { return $tail . '</body>'; }, $html, 1);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
echo $html;
