<?php
// Read/write helpers for content/{lang}.json, used only by the admin panel.
// The public site reads this same content via includes/i18n.php — this
// file is the admin-side counterpart that can also *write*.

define('CONTENT_DIR', dirname(__DIR__, 2) . '/content');
define('BACKUPS_DIR', dirname(__DIR__, 2) . '/data/backups');
define('MAX_BACKUPS_PER_LANG', 30);

function cs_valid_lang($lang) {
    return in_array($lang, ['en', 'fr', 'zh', 'ja'], true);
}

function cs_load($lang) {
    if (!cs_valid_lang($lang)) {
        return [];
    }
    $path = CONTENT_DIR . "/$lang.json";
    if (!is_file($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Save $data as content/{lang}.json. Takes a timestamped backup of the
 * previous version first, and uses an exclusive lock while writing so two
 * admins saving at the same moment can't corrupt the file.
 *
 * Returns true on success, false on failure (and leaves the original file
 * untouched on failure).
 */
function cs_save($lang, $data) {
    if (!cs_valid_lang($lang)) {
        return false;
    }

    $path = CONTENT_DIR . "/$lang.json";
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false; // encoding failed — never write a broken file
    }

    cs_backup($lang);

    $fp = fopen($path, 'c+');
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

function cs_backup($lang) {
    $path = CONTENT_DIR . "/$lang.json";
    if (!is_file($path)) {
        return;
    }
    if (!is_dir(BACKUPS_DIR)) {
        mkdir(BACKUPS_DIR, 0750, true);
    }
    $stamp = date('Ymd-His');
    copy($path, BACKUPS_DIR . "/$lang-$stamp.json");
    cs_prune_backups($lang);
}

function cs_prune_backups($lang) {
    $files = glob(BACKUPS_DIR . "/$lang-*.json");
    if ($files === false || count($files) <= MAX_BACKUPS_PER_LANG) {
        return;
    }
    sort($files); // filenames are timestamp-sortable
    $toRemove = array_slice($files, 0, count($files) - MAX_BACKUPS_PER_LANG);
    foreach ($toRemove as $f) {
        @unlink($f);
    }
}

function cs_list_backups($lang) {
    $files = glob(BACKUPS_DIR . "/$lang-*.json") ?: [];
    rsort($files); // newest first
    return $files;
}

// --- Dot-path get/set, same semantics as includes/i18n.php's enwires_dig() ---

function cs_get($data, $path, $default = null) {
    $segments = explode('.', $path);
    $node = $data;
    foreach ($segments as $segment) {
        if (!is_array($node) || !array_key_exists($segment, $node)) {
            return $default;
        }
        $node = $node[$segment];
    }
    return $node;
}

function cs_set(&$data, $path, $value) {
    $segments = explode('.', $path);
    $ref = &$data;
    foreach ($segments as $i => $segment) {
        if ($i === count($segments) - 1) {
            $ref[$segment] = $value;
        } else {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
    }
}

// --- Array mutation helpers, used for repeater/gallery add/remove/reorder ---

function cs_array_remove_at(&$data, $path, $index) {
    $arr = cs_get($data, $path, []);
    if (!is_array($arr)) return;
    unset($arr[$index]);
    cs_set($data, $path, array_values($arr));
}

function cs_array_move(&$data, $path, $index, $direction) {
    $arr = cs_get($data, $path, []);
    if (!is_array($arr)) return;
    $arr = array_values($arr);
    $target = $index + ($direction === 'up' ? -1 : 1);
    if ($target < 0 || $target >= count($arr) || !isset($arr[$index])) return;
    $tmp = $arr[$index];
    $arr[$index] = $arr[$target];
    $arr[$target] = $tmp;
    cs_set($data, $path, $arr);
}

function cs_array_add(&$data, $path, $blankRow) {
    $arr = cs_get($data, $path, []);
    if (!is_array($arr)) $arr = [];
    $arr[] = $blankRow;
    cs_set($data, $path, array_values($arr));
}
