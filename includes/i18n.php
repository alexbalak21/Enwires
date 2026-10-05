<?php
// Loads the content JSON for CURRENT_LANG (already validated in config.php)
// and exposes t() / t_list() helpers for use in templates.
//
// Content now lives in /content/{lang}.json (edited via /admin/) instead of
// includes/lang/*.php. Nothing about how templates call t() changed — only
// the storage format did — except that array content (repeaters and image
// galleries) now has its own accessor, t_list().
//
// Usage: call t('home.hero.headline') to echo a string, or
//        t_list('home.offer.items') to get an array of rows.

function enwires_load_content($lang) {
    $path = dirname(__DIR__) . "/content/$lang.json";
    if (!is_file($path)) {
        return [];
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

$GLOBALS['__content'] = enwires_load_content(CURRENT_LANG);

/**
 * Look up a dot-path key in a nested array. Returns null if any segment
 * along the path is missing.
 */
function enwires_dig($data, $key) {
    $segments = explode('.', $key);
    $node = $data;
    foreach ($segments as $segment) {
        if (!is_array($node) || !array_key_exists($segment, $node)) {
            return null;
        }
        $node = $node[$segment];
    }
    return $node;
}

/**
 * Translate a scalar string by dot-path key, e.g. t('home.hero.headline').
 *
 * Falls back to the English value if the key is missing from the current
 * language's content, then to the raw key itself if it's missing from
 * English too — so a missing translation or a typo'd key never breaks the
 * page, it just shows English (or the key) instead of a fatal error.
 */
function t($key) {
    $value = enwires_dig($GLOBALS['__content'], $key);
    if (is_string($value)) {
        return $value;
    }

    static $en = null;
    if ($en === null) {
        $en = enwires_load_content('en');
    }
    $fallback = enwires_dig($en, $key);
    return is_string($fallback) ? $fallback : $key;
}

/**
 * Get a repeater/gallery array by dot-path key, e.g.
 * t_list('home.offer.items') or t_list('home.pilot.images').
 *
 * Same English-then-empty fallback behavior as t(), so a page loops over
 * an empty array (renders nothing extra) rather than fatal-erroring if a
 * language's content.json is missing that section entirely.
 */
function t_list($key) {
    $value = enwires_dig($GLOBALS['__content'], $key);
    if (is_array($value)) {
        return $value;
    }

    static $en = null;
    if ($en === null) {
        $en = enwires_load_content('en');
    }
    $fallback = enwires_dig($en, $key);
    return is_array($fallback) ? $fallback : [];
}
