<?php
// Public-side block rendering. Loaded by config.php, so index.php and
// product.php can simply call enw_render_blocks('home' | 'product').

require_once __DIR__ . '/blocks-core.php';

// --- Output helpers ------------------------------------------------------

/**
 * Safe rich text. Everything is HTML-escaped first, then a tiny allow-list
 * is switched back on: the entities editors already type (&mdash; &ndash;
 * &times; ...) and <strong>, <em>, <br>. Nothing else (no scripts, no
 * links, no attributes) can get through.
 */
function enw_rich($s) {
    $s = htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $s = preg_replace(
        '/&amp;(#[0-9]{1,6}|#x[0-9a-fA-F]{1,6}|mdash|ndash|times|nbsp|hellip|rsquo|lsquo|rdquo|ldquo|amp|copy|reg|trade|deg|plusmn|middot|laquo|raquo);/',
        '&$1;',
        $s
    );
    $s = preg_replace('/&lt;(strong|em)&gt;/i', '<$1>', $s);
    $s = preg_replace('/&lt;\/(strong|em)&gt;/i', '</$1>', $s);
    $s = preg_replace('/&lt;br\s*\/?&gt;/i', '<br>', $s);
    return $s;
}

/** Safe value for an HTML attribute (entities like &mdash; become real characters). */
function enw_attr($s) {
    $s = html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function enw_img_src($file) {
    return enw_attr(ASSETS_URL . 'assets/img/' . $file);
}

/** 'bg' | 'charcoal' | 'paper'  =>  CSS class used by style.css */
function enw_band($value) {
    $map = ['bg' => 'band-bg', 'charcoal' => 'band-charcoal', 'paper' => 'band-paper'];
    return $map[$value] ?? 'band-bg';
}

/** Internal page file (e.g. 'product.php') => clean URL in the current language. */
function enw_link_href($file) {
    foreach (enw_site_pages() as $key => $p) {
        if ($p['file'] === $file && $p['slug'] !== null) {
            return enw_page_url($key);
        }
    }
    return PAGE_BASE;
}

function enw_block_attrs(array $b) {
    return ' data-block-id="' . enw_attr($b['id']) . '" data-block-type="' . enw_attr($b['type']) . '"';
}

// --- Loading + rendering -------------------------------------------------

/**
 * Blocks of a page for the CURRENT language, with English fallback applied.
 * In the admin live preview, the unsaved draft is used instead.
 */
function enw_page_blocks($page) {
    if (defined('ENWIRES_PREVIEW') && isset($GLOBALS['__enw_preview_blocks'][$page])) {
        return $GLOBALS['__enw_preview_blocks'][$page];
    }
    $blocks = enw_page_resolved($page, CURRENT_LANG);
    return enw_apply_fallback($blocks, enw_page_resolved($page, 'en'), CURRENT_LANG);
}

/** First image src found in a block of the given type (used for og:image). */
function enw_first_image_src($page, $type) {
    foreach (enw_page_blocks($page) as $b) {
        if ($b['type'] !== $type) continue;
        foreach ($b['fields'] as $v) {
            if (is_array($v) && isset($v['src']) && $v['src'] !== '') {
                return $v['src'];
            }
        }
    }
    return null;
}

function enw_render_blocks($page) {
    if (!enw_structure_exists() && enw_page_defaults($page) === null) {
        error_log('Enwires: content/structure.json is missing — run /admin/migrate-blocks.php once.');
    }
    echo '<main data-enw-blocks>' . "\n";
    foreach (enw_page_blocks($page) as $b) {
        $file = __DIR__ . '/blocks/' . $b['type'] . '.php';
        if (!is_file($file)) continue;
        $f = $b['fields'];
        include $file;
    }
    echo '</main>' . "\n";
}
