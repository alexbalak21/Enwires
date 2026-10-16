<?php
// Admin-side storage for the block builder: loading the editor state,
// saving a page (with backups + conflict detection) and the image library.
//
// Saving writes two things:
//   1. content/{lang}.json      -> this language's translated texts ("blocks") + SEO meta
//   2. content/structure.json   -> the shared structure (order, images, options)

require_once __DIR__ . '/content-store.php';
require_once dirname(__DIR__, 2) . '/includes/blocks-core.php';
require_once __DIR__ . '/uploads.php';

define('BS_LANGS', ['en', 'fr', 'zh', 'ja']);
define('BS_MAX_STRUCTURE_BACKUPS', 30);

/** JSON response helper for the AJAX endpoints. */
function bs_json($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** CSRF check for requests that send the token as a header (JSON bodies). */
function bs_verify_csrf_header() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/** For AJAX endpoints: answer 401 as JSON instead of redirecting to the login page. */
function bs_require_login_json() {
    if (!admin_is_logged_in()) {
        bs_json(['ok' => false, 'error' => 'You are logged out. Please reload the page and log in again.'], 401);
    }
}

// --- Locked write (same approach as cs_save) ----------------------------

function bs_write_locked($path, $json) {
    $fp = @fopen($path, 'c+');
    if (!$fp) {
        return false;
    }
    $ok = false;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        rewind($fp);
        $ok = fwrite($fp, $json . "\n") !== false;
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return $ok;
}

function bs_structure_backup() {
    if (!is_file(ENW_STRUCTURE_FILE)) {
        return;
    }
    if (!is_dir(BACKUPS_DIR)) {
        @mkdir(BACKUPS_DIR, 0775, true);
    }
    @copy(ENW_STRUCTURE_FILE, BACKUPS_DIR . '/structure-' . date('Ymd-His') . '.json');

    $files = glob(BACKUPS_DIR . '/structure-*.json') ?: [];
    if (count($files) > BS_MAX_STRUCTURE_BACKUPS) {
        sort($files);
        foreach (array_slice($files, 0, count($files) - BS_MAX_STRUCTURE_BACKUPS) as $f) {
            @unlink($f);
        }
    }
}

function bs_structure_save(array $structure) {
    $json = json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    bs_structure_backup();
    return bs_write_locked(ENW_STRUCTURE_FILE, $json);
}

// --- Revision (conflict detection) --------------------------------------

/**
 * A fingerprint of everything one save of this page + language can change.
 * If it differs from what the editor loaded, someone else saved in between.
 */
function bs_page_rev($page, $lang) {
    $list = enw_page_structure($page);   // saved, or the built-in default (404 page)
    $data = cs_load($lang);
    $all = (isset($data['blocks']) && is_array($data['blocks'])) ? $data['blocks'] : [];

    $texts = [];
    foreach ($list as $sb) {
        $id = $sb['id'] ?? '';
        if (isset($all[$id])) {
            $texts[$id] = $all[$id];
        }
    }
    $meta = (isset($data[$page]['meta']) && is_array($data[$page]['meta'])) ? $data[$page]['meta'] : [];
    return sha1(json_encode([$list, $texts, $meta]));
}

// --- Editor state --------------------------------------------------------

function bs_meta_of($data, $page) {
    $m = (isset($data[$page]['meta']) && is_array($data[$page]['meta'])) ? $data[$page]['meta'] : [];
    return [
        'title'       => enw_str($m['title'] ?? ''),
        'description' => enw_str($m['description'] ?? ''),
        'keywords'    => enw_str($m['keywords'] ?? ''),
    ];
}

/** Everything the builder needs on first load. */
function bs_editor_state($page, $lang) {
    $blocks = enw_page_resolved($page, $lang);       // no fallback: empty stays empty
    $enBlocks = ($lang === 'en') ? $blocks : enw_page_resolved($page, 'en');

    $enById = [];
    foreach ($enBlocks as $eb) {
        $enById[$eb['id']] = $eb['fields'];
    }

    return [
        'blocks' => $blocks,
        'meta'   => bs_meta_of(cs_load($lang), $page),
        'rev'    => bs_page_rev($page, $lang),
        'en'     => ['blocks' => $enById, 'meta' => bs_meta_of(cs_load('en'), $page)],
    ];
}

/**
 * File names in assets/img that can be reused. Logos are left out of the
 * normal photo list; pass true (the header/footer editor does) to list them.
 */
function bs_image_library($includeLogos = false) {
    $out = [];
    $pattern = $includeLogos ? '/\.(jpe?g|png|webp|gif|svg)$/i' : '/\.(jpe?g|png|webp|gif)$/i';
    foreach (glob(UPLOAD_DIR . '/*') ?: [] as $path) {
        $name = basename($path);
        if (!preg_match($pattern, $name)) continue;
        if (!$includeLogos && (stripos($name, 'logo') !== false || stripos($name, 'favicon') !== false)) continue;
        $out[] = $name;
    }
    sort($out, SORT_NATURAL | SORT_FLAG_CASE);
    return $out;
}

// --- Saving --------------------------------------------------------------

/**
 * Save one page in one language.
 * Returns ['ok' => true, 'rev' => ..., 'blocks' => ...] or
 *         ['ok' => false, 'error' => '...', 'conflict' => bool].
 */
function bs_save_page($page, $lang, array $draftBlocks, array $meta, $baseRev) {
    $pages = enw_block_pages();
    if (!isset($pages[$page]) || !cs_valid_lang($lang)) {
        return ['ok' => false, 'error' => 'Unknown page or language.'];
    }

    if (!hash_equals(bs_page_rev($page, $lang), (string) $baseRev)) {
        return [
            'ok' => false, 'conflict' => true,
            'error' => 'Someone else saved this page while you were editing. Reload the page to see their changes (your unsaved edits here would otherwise overwrite them).',
        ];
    }

    [$newStructure, $newTexts] = enw_split_draft($draftBlocks, $lang);

    $structure = enw_structure_load();
    $oldList = enw_page_structure($page);
    $oldIds = [];
    foreach ($oldList as $sb) {
        if (is_array($sb) && isset($sb['id'])) $oldIds[] = $sb['id'];
    }
    $newIds = array_map(fn($sb) => $sb['id'], $newStructure);
    $removedIds = array_values(array_diff($oldIds, $newIds));

    // 1) This language's texts + SEO meta.
    $data = cs_load($lang);
    $all = (isset($data['blocks']) && is_array($data['blocks'])) ? $data['blocks'] : [];
    foreach ($oldIds as $id) {
        unset($all[$id]);
    }
    foreach ($newTexts as $id => $t) {
        $all[$id] = $t;
    }
    $data['blocks'] = $all;

    foreach (['title', 'description', 'keywords'] as $k) {
        $v = enw_clean_text($meta[$k] ?? '');
        if ($v !== '' || $lang === 'en') {
            cs_set($data, "$page.meta.$k", $v);
        } elseif (isset($data[$page]['meta'][$k])) {
            unset($data[$page]['meta'][$k]); // empty non-English => fall back to English
        }
    }

    if (!cs_save($lang, $data)) {
        return ['ok' => false, 'error' => 'Could not write content/' . $lang . '.json — check that the file is writable by the web server.'];
    }

    // 2) The shared structure.
    $structure[$page] = $newStructure;
    if (!bs_structure_save($structure)) {
        return ['ok' => false, 'error' => 'Texts were saved, but content/structure.json could not be written — check that the file is writable by the web server, then save again.'];
    }

    // 3) Tidy: drop the texts of deleted blocks from the other languages.
    if ($removedIds) {
        foreach (BS_LANGS as $other) {
            if ($other === $lang) continue;
            $d = cs_load($other);
            $changed = false;
            foreach ($removedIds as $id) {
                if (isset($d['blocks'][$id])) {
                    unset($d['blocks'][$id]);
                    $changed = true;
                }
            }
            if ($changed) cs_save($other, $d);
        }
    }

    // Return the cleaned blocks so the editor shows exactly what was stored
    // (new ids assigned, text cleaned, ...).
    return [
        'ok'     => true,
        'rev'    => bs_page_rev($page, $lang),
        'blocks' => enw_page_resolved($page, $lang),
    ];
}
