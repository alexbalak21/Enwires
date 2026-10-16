<?php
// Public-side layout helpers. Loaded by config.php (after PAGE_BASE exists).
// header.php and footer.php read everything they show from enw_layout().

require_once __DIR__ . '/layout-core.php';

/** Header / footer data for the CURRENT language (or the unsaved draft in the admin preview). */
function enw_layout() {
    if (defined('ENWIRES_PREVIEW') && isset($GLOBALS['__enw_preview_layout'])) {
        return $GLOBALS['__enw_preview_layout'];
    }
    static $cache = null;
    if ($cache === null) {
        $cache = enw_layout_for(CURRENT_LANG);
    }
    return $cache;
}

/** 'home' | 'product' | 'contact' | 'notfound' — which page is being shown. */
function enw_current_page_key() {
    if (defined('ENWIRES_PAGE_KEY')) {
        return ENWIRES_PAGE_KEY;
    }
    $file = basename($_SERVER['SCRIPT_NAME'] ?? '');
    foreach (enw_site_pages() as $key => $p) {
        if ($p['file'] === $file) return $key;
    }
    return '';
}

/** Clean URL slug of a page: '' for home, 'product', 'contact'. */
function enw_page_slug($key) {
    $pages = enw_site_pages();
    return (isset($pages[$key]) && $pages[$key]['slug'] !== null) ? $pages[$key]['slug'] : '';
}

/** URL of a page in the CURRENT language, e.g. '/fr/product'. */
function enw_page_url($key) {
    return PAGE_BASE . enw_page_slug($key);
}

function enw_nav_href(array $item) {
    if ($item['kind'] === 'url') {
        return $item['url'] !== '' ? $item['url'] : '#';
    }
    return enw_page_url($item['page']);
}

/** A link's text; never empty, so a link can't silently disappear. */
function enw_nav_label(array $item) {
    if ($item['label'] !== '') {
        return $item['label'];
    }
    if ($item['kind'] === 'page') {
        $pages = enw_site_pages();
        return $pages[$item['page']]['label'] ?? '';
    }
    return $item['url'];
}

/** Extra attributes for a link that should open in a new tab. */
function enw_nav_target(array $item) {
    return !empty($item['new_tab']) ? ' target="_blank" rel="noopener"' : '';
}
