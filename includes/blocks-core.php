<?php
// Block data layer. Pure functions with NO dependency on the current page
// language, so the public site, the admin builder and the preview endpoint
// can all use it.
//
// Storage model ("shared structure, translated text"):
//
//   content/structure.json      what every language shares
//       { "home": [ { "id": "b_hero", "type": "hero", "shared": {...} }, ... ] }
//
//   content/{lang}.json         the translated text, under "blocks"
//       { "blocks": { "b_hero": { "headline": "...", ... } }, ... }
//
// A "resolved" block is the merged, template-ready view of both:
//   [ 'id' => 'b_hero', 'type' => 'hero', 'fields' => [ 'headline' => '...', ... ] ]
//
//   text / textarea  => string
//   select / link    => string
//   checkbox         => bool
//   image            => [ 'src', 'alt', 'caption' ]
//   gallery          => [ [ 'id', 'src', 'alt' ], ... ]
//   repeater         => [ [ 'id', <sub-field> => string, ... ], ... ]

require_once __DIR__ . '/block-registry.php';

define('ENW_CONTENT_DIR', dirname(__DIR__) . '/content');
define('ENW_STRUCTURE_FILE', ENW_CONTENT_DIR . '/structure.json');
define('ENW_MAX_BLOCKS', 60);
define('ENW_MAX_ITEMS', 40);
define('ENW_MAX_TEXT', 8000);

// --- Loading ------------------------------------------------------------

function enw_lang_content($lang) {
    if (!in_array($lang, ['en', 'fr', 'zh', 'ja'], true)) {
        return [];
    }
    $path = ENW_CONTENT_DIR . "/$lang.json";
    if (!is_file($path)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

/** Look up 'a.b.c' in a nested array; null if any step is missing. */
function enw_dig($data, $path) {
    $node = $data;
    foreach (explode('.', $path) as $seg) {
        if (!is_array($node) || !array_key_exists($seg, $node)) {
            return null;
        }
        $node = $node[$seg];
    }
    return $node;
}

function enw_structure_exists() {
    return is_file(ENW_STRUCTURE_FILE);
}

function enw_structure_load() {
    if (!is_file(ENW_STRUCTURE_FILE)) {
        return [];
    }
    $data = json_decode((string) file_get_contents(ENW_STRUCTURE_FILE), true);
    return is_array($data) ? $data : [];
}

// --- Small validators / cleaners ---------------------------------------

function enw_valid_id($id) {
    return is_string($id) && preg_match('/^[a-z0-9_]{1,40}$/', $id) === 1;
}

function enw_new_id($prefix) {
    return $prefix . substr(bin2hex(random_bytes(4)), 0, 8);
}

/** Keep only a plain file name (no folders, no odd characters). */
function enw_clean_filename($name) {
    $name = basename((string) $name);
    if ($name === '' || $name[0] === '.' || preg_match('/^[A-Za-z0-9._\-]+$/', $name) !== 1) {
        return '';
    }
    return $name;
}

function enw_clean_text($value) {
    if (!is_string($value)) {
        return '';
    }
    $value = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, ENW_MAX_TEXT, 'UTF-8');
    }
    return substr($value, 0, ENW_MAX_TEXT);
}

function enw_str($value) {
    return is_string($value) ? $value : '';
}

// --- Structure + texts  =>  resolved (no fallback) ---------------------

/**
 * Merge one block's shared data with its translated texts for one language.
 * Returns null for an unknown block type.
 */
function enw_resolve_block(array $sb, array $texts) {
    $types = enw_block_types();
    $type = $sb['type'] ?? '';
    if (!isset($types[$type]) || !enw_valid_id($sb['id'] ?? null)) {
        return null;
    }
    $shared = (isset($sb['shared']) && is_array($sb['shared'])) ? $sb['shared'] : [];
    $out = [];

    foreach ($types[$type]['fields'] as $fd) {
        $n = $fd['name'];
        $t = $texts[$n] ?? null;

        switch ($fd['type']) {
            case 'text':
            case 'textarea':
                $out[$n] = enw_str($t);
                break;

            case 'select':
                $v = $shared[$n] ?? ($fd['default'] ?? '');
                $out[$n] = (is_string($v) && array_key_exists($v, $fd['options'])) ? $v : ($fd['default'] ?? '');
                break;

            case 'link':
                $v = $shared[$n] ?? ($fd['default'] ?? '');
                $out[$n] = (is_string($v) && array_key_exists($v, enw_link_options())) ? $v : ($fd['default'] ?? 'index.php');
                break;

            case 'checkbox':
                $out[$n] = array_key_exists($n, $shared) ? !empty($shared[$n]) : !empty($fd['default']);
                break;

            case 'image':
                $img = (isset($shared[$n]) && is_array($shared[$n])) ? $shared[$n] : [];
                $ti = is_array($t) ? $t : [];
                $out[$n] = [
                    'src'     => enw_clean_filename($img['src'] ?? ''),
                    'alt'     => enw_str($ti['alt'] ?? ''),
                    'caption' => enw_str($ti['caption'] ?? ''),
                ];
                break;

            case 'gallery':
                $items = (isset($shared[$n]) && is_array($shared[$n])) ? $shared[$n] : [];
                $rows = [];
                foreach ($items as $it) {
                    if (!is_array($it) || !enw_valid_id($it['id'] ?? null)) continue;
                    $ti = (is_array($t) && isset($t[$it['id']]) && is_array($t[$it['id']])) ? $t[$it['id']] : [];
                    $rows[] = [
                        'id'  => $it['id'],
                        'src' => enw_clean_filename($it['src'] ?? ''),
                        'alt' => enw_str($ti['alt'] ?? ''),
                    ];
                }
                $out[$n] = $rows;
                break;

            case 'repeater':
                $items = (isset($shared[$n]) && is_array($shared[$n])) ? $shared[$n] : [];
                $rows = [];
                foreach ($items as $it) {
                    if (!is_array($it) || !enw_valid_id($it['id'] ?? null)) continue;
                    $ti = (is_array($t) && isset($t[$it['id']]) && is_array($t[$it['id']])) ? $t[$it['id']] : [];
                    $row = ['id' => $it['id']];
                    foreach ($fd['item_fields'] as $sub) {
                        $row[$sub['name']] = enw_str($ti[$sub['name']] ?? '');
                    }
                    $rows[] = $row;
                }
                $out[$n] = $rows;
                break;
        }
    }

    return ['id' => $sb['id'], 'type' => $type, 'fields' => $out];
}

/**
 * Built-in starting content for pages that work before anyone edits them
 * (today: the 404 page). Returns null for pages without defaults.
 * Shape: ['structure' => [...], 'texts' => [lang => [blockId => texts]]]
 */
function enw_page_defaults($page) {
    if ($page !== 'notfound') {
        return null;
    }
    return [
        'structure' => [
            ['id' => 'notfound_intro', 'type' => 'page-intro', 'shared' => []],
            ['id' => 'notfound_cta', 'type' => 'cta', 'shared' => ['button_link' => 'index.php', 'bg' => 'charcoal']],
        ],
        'texts' => [
            'en' => [
                'notfound_intro' => ['eyebrow' => 'Error 404', 'heading' => 'Page not found',
                    'text' => "The page you're looking for doesn't exist, or it has moved."],
                'notfound_cta' => ['heading' => "Let's get you back on track", 'button' => 'Back to home'],
            ],
            'fr' => [
                'notfound_intro' => ['eyebrow' => 'Erreur 404', 'heading' => 'Page introuvable',
                    'text' => "La page que vous recherchez n'existe pas ou a été déplacée."],
                'notfound_cta' => ['heading' => 'Reprenons le bon chemin', 'button' => "Retour à l'accueil"],
            ],
            'zh' => [
                'notfound_intro' => ['eyebrow' => '错误 404', 'heading' => '页面未找到',
                    'text' => '您访问的页面不存在，或已被移动。'],
                'notfound_cta' => ['heading' => '让我们带您回到正轨', 'button' => '返回首页'],
            ],
            'ja' => [
                'notfound_intro' => ['eyebrow' => 'エラー 404', 'heading' => 'ページが見つかりません',
                    'text' => 'お探しのページは存在しないか、移動した可能性があります。'],
                'notfound_cta' => ['heading' => 'トップページに戻りましょう', 'button' => 'ホームに戻る'],
            ],
        ],
    ];
}

/** True while a page with built-in defaults has never been saved. */
function enw_page_is_default($page) {
    $structure = enw_structure_load();
    return enw_page_defaults($page) !== null && !(isset($structure[$page]) && is_array($structure[$page]));
}

/** The shared block list of a page: the saved one, or the built-in default. */
function enw_page_structure($page) {
    $structure = enw_structure_load();
    if (isset($structure[$page]) && is_array($structure[$page])) {
        return $structure[$page];
    }
    $d = enw_page_defaults($page);
    return $d ? $d['structure'] : [];
}

/** All blocks of a page for one language, WITHOUT English fallback. */
function enw_page_resolved($page, $lang) {
    $list = enw_page_structure($page);
    $content = enw_lang_content($lang);
    $langBlocks = (isset($content['blocks']) && is_array($content['blocks'])) ? $content['blocks'] : [];
    $defaults = enw_page_defaults($page);
    $defTexts = ($defaults && isset($defaults['texts'][$lang])) ? $defaults['texts'][$lang] : [];

    $out = [];
    foreach ($list as $sb) {
        if (!is_array($sb) || !enw_valid_id($sb['id'] ?? null)) continue;
        if (isset($langBlocks[$sb['id']]) && is_array($langBlocks[$sb['id']])) {
            $texts = $langBlocks[$sb['id']];
        } else {
            $texts = (isset($defTexts[$sb['id']]) && is_array($defTexts[$sb['id']])) ? $defTexts[$sb['id']] : [];
        }
        $rb = enw_resolve_block($sb, $texts);
        if ($rb) $out[] = $rb;
    }
    return $out;
}

// --- English fallback ---------------------------------------------------

/**
 * For non-English languages: any EMPTY translated text falls back to the
 * English text of the same block / item. (English itself never falls back.)
 */
function enw_apply_fallback(array $blocks, array $enBlocks, $lang) {
    if ($lang === 'en') {
        return $blocks;
    }
    $types = enw_block_types();
    $enById = [];
    foreach ($enBlocks as $eb) {
        $enById[$eb['id']] = $eb['fields'];
    }

    foreach ($blocks as $bi => $b) {
        $en = $enById[$b['id']] ?? [];
        foreach ($types[$b['type']]['fields'] as $fd) {
            $n = $fd['name'];
            switch ($fd['type']) {
                case 'text':
                case 'textarea':
                    if (($blocks[$bi]['fields'][$n] ?? '') === '') {
                        $blocks[$bi]['fields'][$n] = enw_str($en[$n] ?? '');
                    }
                    break;

                case 'image':
                    foreach (['alt', 'caption'] as $k) {
                        if (($blocks[$bi]['fields'][$n][$k] ?? '') === '') {
                            $blocks[$bi]['fields'][$n][$k] = enw_str($en[$n][$k] ?? '');
                        }
                    }
                    break;

                case 'gallery':
                    $enItems = [];
                    foreach (($en[$n] ?? []) as $it) $enItems[$it['id']] = $it;
                    foreach ($blocks[$bi]['fields'][$n] as $ii => $it) {
                        if ($it['alt'] === '') {
                            $blocks[$bi]['fields'][$n][$ii]['alt'] = enw_str($enItems[$it['id']]['alt'] ?? '');
                        }
                    }
                    break;

                case 'repeater':
                    $enItems = [];
                    foreach (($en[$n] ?? []) as $it) $enItems[$it['id']] = $it;
                    foreach ($blocks[$bi]['fields'][$n] as $ii => $it) {
                        foreach ($fd['item_fields'] as $sub) {
                            $k = $sub['name'];
                            if (($it[$k] ?? '') === '') {
                                $blocks[$bi]['fields'][$n][$ii][$k] = enw_str($enItems[$it['id']][$k] ?? '');
                            }
                        }
                    }
                    break;
            }
        }
    }
    return $blocks;
}

// --- Draft  =>  shared structure + translated texts --------------------

/**
 * Take the editor's draft (resolved-shaped blocks), clean/validate it and
 * split it into:
 *   [0] shared structure entries   (go to structure.json)
 *   [1] translated texts by block  (go to {lang}.json under "blocks")
 *
 * Rule for non-English languages: an empty text is NOT stored, so the
 * English text is used instead (see enw_apply_fallback).
 */
function enw_split_draft(array $draftBlocks, $lang) {
    $types = enw_block_types();
    $structure = [];
    $texts = [];
    $seen = [];

    foreach (array_slice(array_values($draftBlocks), 0, ENW_MAX_BLOCKS) as $db) {
        if (!is_array($db)) continue;
        $type = $db['type'] ?? '';
        if (!isset($types[$type])) continue;

        $id = $db['id'] ?? '';
        if (!enw_valid_id($id) || isset($seen[$id])) {
            $id = enw_new_id('b_');
        }
        $seen[$id] = true;

        $in = (isset($db['fields']) && is_array($db['fields'])) ? $db['fields'] : [];
        $shared = [];
        $t = [];

        foreach ($types[$type]['fields'] as $fd) {
            $n = $fd['name'];
            $v = $in[$n] ?? null;

            switch ($fd['type']) {
                case 'text':
                case 'textarea':
                    $s = enw_clean_text($v);
                    if ($s !== '' || $lang === 'en') $t[$n] = $s;
                    break;

                case 'select':
                    $shared[$n] = (is_string($v) && array_key_exists($v, $fd['options'])) ? $v : ($fd['default'] ?? '');
                    break;

                case 'link':
                    $shared[$n] = (is_string($v) && array_key_exists($v, enw_link_options())) ? $v : ($fd['default'] ?? 'index.php');
                    break;

                case 'checkbox':
                    $shared[$n] = !empty($v);
                    break;

                case 'image':
                    $img = is_array($v) ? $v : [];
                    $shared[$n] = ['src' => enw_clean_filename($img['src'] ?? '')];
                    $ti = ['alt' => enw_clean_text($img['alt'] ?? '')];
                    if (!empty($fd['caption'])) {
                        $ti['caption'] = enw_clean_text($img['caption'] ?? '');
                    }
                    foreach ($ti as $k => $s) {
                        if ($s === '' && $lang !== 'en') unset($ti[$k]);
                    }
                    if ($ti) $t[$n] = $ti;
                    break;

                case 'gallery':
                    $items = is_array($v) ? array_slice(array_values($v), 0, ENW_MAX_ITEMS) : [];
                    $sl = [];
                    $tl = [];
                    $seenI = [];
                    foreach ($items as $it) {
                        if (!is_array($it)) continue;
                        $iid = $it['id'] ?? '';
                        if (!enw_valid_id($iid) || isset($seenI[$iid])) $iid = enw_new_id('i_');
                        $seenI[$iid] = true;
                        $sl[] = ['id' => $iid, 'src' => enw_clean_filename($it['src'] ?? '')];
                        $alt = enw_clean_text($it['alt'] ?? '');
                        if ($alt !== '' || $lang === 'en') $tl[$iid] = ['alt' => $alt];
                    }
                    $shared[$n] = $sl;
                    if ($tl) $t[$n] = $tl;
                    break;

                case 'repeater':
                    $items = is_array($v) ? array_slice(array_values($v), 0, ENW_MAX_ITEMS) : [];
                    $sl = [];
                    $tl = [];
                    $seenI = [];
                    foreach ($items as $it) {
                        if (!is_array($it)) continue;
                        $iid = $it['id'] ?? '';
                        if (!enw_valid_id($iid) || isset($seenI[$iid])) $iid = enw_new_id('i_');
                        $seenI[$iid] = true;
                        $sl[] = ['id' => $iid];
                        $row = [];
                        foreach ($fd['item_fields'] as $sub) {
                            $s = enw_clean_text($it[$sub['name']] ?? '');
                            if ($s !== '' || $lang === 'en') $row[$sub['name']] = $s;
                        }
                        if ($row) $tl[$iid] = $row;
                    }
                    $shared[$n] = $sl;
                    if ($tl) $t[$n] = $tl;
                    break;
            }
        }

        $structure[] = ['id' => $id, 'type' => $type, 'shared' => $shared];
        if ($t) $texts[$id] = $t;
    }

    return [$structure, $texts];
}

/**
 * Draft => template-ready blocks for a language, exactly as they would look
 * if saved (used by the live preview).
 */
function enw_draft_to_blocks(array $draftBlocks, $page, $lang) {
    [$structure, $texts] = enw_split_draft($draftBlocks, $lang);
    $out = [];
    foreach ($structure as $sb) {
        $rb = enw_resolve_block($sb, $texts[$sb['id']] ?? []);
        if ($rb) $out[] = $rb;
    }
    return enw_apply_fallback($out, enw_page_resolved($page, 'en'), $lang);
}
