<?php
// Target of every language-switcher link (see includes/header.php).
//
// Why this exists rather than linking straight to the target language URL:
// a plain link to e.g. "/fr/" would still arrive at the server carrying
// the OLD "enwires_lang" cookie (cookies only update once a response comes
// back), and language-detect.php would read that stale cookie on "/" and
// redirect back to the wrong language. Routing through here sets the new
// cookie and redirects in the same response, so the follow-up request
// always carries the correct, just-chosen language.

require_once __DIR__ . '/includes/config.php';

$supported = array_keys($GLOBALS['LANGUAGES']);

$target = isset($_GET['lang']) ? $_GET['lang'] : 'en';
if (!in_array($target, $supported, true)) {
    $target = 'en';
}

// Only allow returning to a known page filename (or '' for home) — never
// pass this straight into the redirect unchecked, to avoid turning this
// into an open redirect.
$returnPage = isset($_GET['to']) ? basename($_GET['to']) : '';
if (!in_array($returnPage, ['', 'index.php', 'product.php', 'contact.php'], true)) {
    $returnPage = '';
}

setcookie('enwires_lang', $target, time() + 60 * 60 * 24 * 365, '/');

$prefix = $GLOBALS['LANGUAGES'][$target]['prefix'];
$destination = '/' . $prefix . ($returnPage === 'index.php' ? '' : $returnPage);

header('Location: ' . $destination, true, 302);
exit;
