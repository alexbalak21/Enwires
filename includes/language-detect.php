<?php
// Only ever included from the TRUE homepage entry — someone visiting "/" or
// "/index.php" directly, not via a /fr/, /zh/, /ja/ wrapper, and never from
// product.php or contact.php. That scoping is deliberate: a shared or
// bookmarked deep link should never get redirected out from under someone
// just because their browser's language differs.
//
// Must run before any HTML output (it may send a redirect header).

/**
 * Parse an Accept-Language header and return the best-matching supported
 * language code, or null if none of the visitor's preferences are
 * supported. Handles quality values ("fr-FR;q=0.8") and region subtags
 * ("zh-CN" -> "zh").
 */
function enwires_pick_browser_language($header, $supported) {
    if (!$header) {
        return null;
    }

    $best = null;
    $bestQ = 0.0;

    foreach (explode(',', $header) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }

        $pieces = explode(';', $part);
        $tag = strtolower(trim($pieces[0]));
        $q = 1.0;

        if (isset($pieces[1])) {
            $qPart = trim($pieces[1]);
            if (stripos($qPart, 'q=') === 0) {
                $q = (float) substr($qPart, 2);
            }
        }

        $primary = substr($tag, 0, 2); // e.g. "fr-FR" -> "fr", "zh-Hans-CN" -> "zh"

        if (in_array($primary, $supported, true) && $q > $bestQ) {
            $best = $primary;
            $bestQ = $q;
        }
    }

    return $best;
}

$supported = array_keys($GLOBALS['LANGUAGES']); // ['en', 'fr', 'zh', 'ja']

if (isset($_COOKIE['enwires_lang']) && in_array($_COOKIE['enwires_lang'], $supported, true)) {
    // Returning visitor: respect whatever language they last actually saw —
    // this cookie is refreshed on every page load (see includes/header.php),
    // so a manual switch always wins over the original browser detection.
    $preferred = $_COOKIE['enwires_lang'];
} else {
    // First-ever visit: guess from the browser's language preferences.
    $preferred = enwires_pick_browser_language($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', $supported);
}

if ($preferred && $preferred !== 'en' && !headers_sent()) {
    header('Location: /' . $GLOBALS['LANGUAGES'][$preferred]['prefix'], true, 302);
    header('Vary: Accept-Language, Cookie');
    exit;
}
