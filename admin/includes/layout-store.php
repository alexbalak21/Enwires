<?php
// Admin-side storage for the header / footer settings.
//
// Saving writes two things (same idea as the block builder):
//   1. content/{lang}.json  -> this language's translated texts ("layout")
//   2. content/site.json    -> the settings every language shares

require_once __DIR__ . '/blocks-store.php';
require_once dirname(__DIR__, 2) . '/includes/layout-core.php';

define('LS_MAX_SITE_BACKUPS', 30);

/** Fingerprint of everything one save can change (conflict detection). */
function ls_rev($lang) {
    $data = cs_load($lang);
    return sha1(json_encode([enw_site_load(), $data['layout'] ?? null]));
}

function ls_site_backup() {
    if (!is_file(ENW_SITE_FILE)) {
        return;
    }
    if (!is_dir(BACKUPS_DIR)) {
        @mkdir(BACKUPS_DIR, 0775, true);
    }
    @copy(ENW_SITE_FILE, BACKUPS_DIR . '/site-' . date('Ymd-His') . '.json');

    $files = glob(BACKUPS_DIR . '/site-*.json') ?: [];
    if (count($files) > LS_MAX_SITE_BACKUPS) {
        sort($files);
        foreach (array_slice($files, 0, count($files) - LS_MAX_SITE_BACKUPS) as $f) {
            @unlink($f);
        }
    }
}

function ls_site_save(array $shared) {
    $json = json_encode($shared, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    ls_site_backup();
    return bs_write_locked(ENW_SITE_FILE, $json);
}

/** Everything the editor needs: this language's draft, English (for placeholders), revision. */
function ls_state($lang) {
    $shared = enw_layout_shared();
    return [
        'draft' => enw_layout_flat($shared, enw_layout_bag(cs_load($lang)), $lang === 'en'),
        'en'    => enw_layout_flat($shared, enw_layout_bag(cs_load('en')), true),
        'rev'   => ls_rev($lang),
    ];
}

/**
 * Save the header / footer in one language.
 * Returns ['ok' => true, 'rev' => ..., 'draft' => ...] or ['ok' => false, 'error' => ..., 'conflict' => bool].
 */
function ls_save($lang, array $draft, $baseRev) {
    if (!cs_valid_lang($lang)) {
        return ['ok' => false, 'error' => 'Unknown language.'];
    }
    if (!hash_equals(ls_rev($lang), (string) $baseRev)) {
        return [
            'ok' => false, 'conflict' => true,
            'error' => 'Someone else saved these settings while you were editing. Reload the page to see their changes (saving now would overwrite them).',
        ];
    }

    [$shared, $texts, $errors] = enw_layout_split_draft($draft);
    if ($errors) {
        return ['ok' => false, 'error' => implode(' ', $errors)];
    }

    // 1) This language's texts.
    $data = cs_load($lang);
    $data['layout'] = $texts;
    if (!cs_save($lang, $data)) {
        return ['ok' => false, 'error' => 'Could not write content/' . $lang . '.json — check that the file is writable by the web server.'];
    }

    // 2) The shared settings.
    if (!ls_site_save($shared)) {
        return ['ok' => false, 'error' => 'Texts were saved, but content/site.json could not be written — check that the content folder and file are writable by the web server, then save again.'];
    }

    // 3) Tidy: drop link labels of removed links from the other languages.
    $keep = [
        'nav'        => array_map(fn($it) => $it['id'], $shared['nav']),
        'footer_nav' => array_map(fn($it) => $it['id'], $shared['footer_nav']),
    ];
    foreach (BS_LANGS as $other) {
        if ($other === $lang) continue;
        $d = cs_load($other);
        $changed = false;
        foreach ($keep as $key => $ids) {
            if (!isset($d['layout'][$key]) || !is_array($d['layout'][$key])) continue;
            foreach (array_keys($d['layout'][$key]) as $id) {
                if (!in_array($id, $ids, true)) {
                    unset($d['layout'][$key][$id]);
                    $changed = true;
                }
            }
        }
        if ($changed) cs_save($other, $d);
    }

    $state = ls_state($lang);
    return ['ok' => true, 'rev' => $state['rev'], 'draft' => $state['draft']];
}
