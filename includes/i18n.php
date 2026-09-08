<?php
// Loads the string table for CURRENT_LANG (already validated in config.php)
// and exposes a small t() helper for use in templates.
//
// Usage in a page/template: call t('home.hero.headline') to echo a string.

$GLOBALS['__i18n_strings'] = require __DIR__ . '/lang/' . CURRENT_LANG . '.php';

/**
 * Translate a string by key.
 *
 * Falls back to the English string if the key is missing from the current
 * language file, then to the raw key itself if it's missing from English
 * too — so a missing/typo'd translation never breaks the page, it just
 * shows English (or the key) instead of a fatal error.
 */
function t($key) {
    if (isset($GLOBALS['__i18n_strings'][$key])) {
        return $GLOBALS['__i18n_strings'][$key];
    }

    static $en = null;
    if ($en === null) {
        $en = require __DIR__ . '/lang/en.php';
    }

    return $en[$key] ?? $key;
}
