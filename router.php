<?php
// Front controller: turns clean URLs into pages, and anything else into the 404 page.
//
//   /            -> index.php        /fr/            -> French home
//   /product     -> product.php      /fr/product     -> French product page
//   /contact     -> contact.php      /ja/contact ... (languages: fr, zh, ja; English has no prefix)
//   anything else -> 404 page (with a real 404 status code)
//
// Old addresses keep working: /product.php, /fr/contact.php ... are redirected (301)
// to the clean URL.
//
// How it is reached:
//   Apache   .htaccess sends every URL that is not a real file/folder here.
//   Local    php -S localhost:8000 router.php     (the built-in server ignores .htaccess)

$r_pages = [
    ''        => ['home',    'index.php'],
    'product' => ['product', 'product.php'],
    'contact' => ['contact', 'contact.php'],
];

function enw_router_redirect($to, $code = 301) {
    header('Location: ' . $to, true, $code);
    exit;
}

// --- 1. Normalise the requested path --------------------------------------
$r_uri   = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$r_path  = '/' . ltrim((string) preg_replace('#/+#', '/', rawurldecode($r_uri)), '/');
$r_query = (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '') ? '?' . $_SERVER['QUERY_STRING'] : '';

if (strpos($r_path, '..') !== false || strpos($r_path, "\0") !== false) {
    http_response_code(404);
    exit('404 Not Found');
}

// --- 2. PHP's built-in server only: let real files (css, images, /admin/...) through ----
if (PHP_SAPI === 'cli-server') {
    $r_isLegacy   = preg_match('#^/(?:(?:fr|zh|ja)/)?(?:index|product|contact)\.php$#', $r_path);
    $r_isLangRoot = preg_match('#^/(?:fr|zh|ja)/?$#', $r_path);
    if (!$r_isLegacy && !$r_isLangRoot && $r_path !== '/') {
        $r_file = __DIR__ . $r_path;
        if (is_file($r_file) && basename($r_path) !== 'router.php') {
            return false;
        }
        if (is_dir($r_file) && is_file(rtrim($r_file, '/') . '/index.php')) {
            return false;
        }
    }
}

// --- 3. Old .php addresses -> clean URLs (301) ------------------------------
if (preg_match('#^/(?:(fr|zh|ja)/)?(index|product|contact)\.php$#', $r_path, $m)) {
    enw_router_redirect('/' . (!empty($m[1]) ? $m[1] . '/' : '') . ($m[2] === 'index' ? '' : $m[2]) . $r_query);
}

// Anything that still looks like a file (missing image, script, ...) gets a plain 404, not a full page.
if (preg_match('#\.[A-Za-z0-9]{1,6}$#', $r_path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('404 Not Found');
}

// --- 4. Language prefix ----------------------------------------------------
$r_lang = null;
$r_rest = ltrim($r_path, '/');
if (preg_match('#^(fr|zh|ja)(?:/(.*))?$#', $r_rest, $m)) {
    $r_lang = $m[1];
    $r_rest = isset($m[2]) ? $m[2] : '';
    if ($r_path === '/' . $r_lang) {
        enw_router_redirect('/' . $r_lang . '/' . $r_query);          // /fr  ->  /fr/
    }
}
$r_base = '/' . ($r_lang !== null ? $r_lang . '/' : '');

// --- 5. One canonical spelling: no trailing slash, lower case -------------------
if ($r_rest !== '' && substr($r_rest, -1) === '/') {
    enw_router_redirect($r_base . rtrim($r_rest, '/') . $r_query);    // /product/  ->  /product
}
$r_lower = strtolower($r_rest);
if ($r_rest !== $r_lower && isset($r_pages[$r_lower])) {
    enw_router_redirect($r_base . $r_lower . $r_query);                // /Product  ->  /product
}

// --- 6. Dispatch -----------------------------------------------------------------
if (isset($r_pages[$r_rest])) {
    list($r_key, $r_script) = $r_pages[$r_rest];
    if ($r_lang !== null) {
        $lang = $r_lang;   // the page templates read $lang to pick the language
    }
    define('ENWIRES_ROUTED', true);
    define('ENWIRES_PAGE_KEY', $r_key);
    define('ENWIRES_PAGE_FILE', $r_script);
    require __DIR__ . '/' . $r_script;
    return;
}

// --- 7. Not found ----------------------------------------------------------------
http_response_code(404);
if ($r_lang !== null) {
    $lang = $r_lang;
} elseif (isset($_COOKIE['enwires_lang']) && in_array($_COOKIE['enwires_lang'], ['en', 'fr', 'zh', 'ja'], true)) {
    $lang = $_COOKIE['enwires_lang'];   // show the 404 in the language the visitor was using
}
define('ENWIRES_ROUTED', true);
define('ENWIRES_PAGE_KEY', 'notfound');
define('ENWIRES_PAGE_FILE', '404.php');
require __DIR__ . '/404.php';
