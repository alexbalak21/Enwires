<?php
// System check — what EXACTLY happens when PHP tries to write?
//
// Standalone on purpose: it needs nothing from the CMS except the admin login,
// so it keeps working even when other parts of the site are broken.
//
// When you press "Run the full test" it:
//   1. tries 15 different file operations in every folder of the site (create,
//      write, append, rename, copy, delete, new sub-folder, .php / .txt files ...)
//      and in the temp folders outside the site, for comparison;
//   2. opens your real content / database files and rewrites one byte with itself
//      (nothing changes) to see whether existing files can be written;
//   3. tests SQLite: your real data/admin.sqlite, and brand-new scratch databases
//      in every folder, with different journal modes;
//   4. explains what it found, and gives you a plain-text report to copy.
// Everything it creates is deleted again (names start with "syscheck-").

require_once __DIR__ . '/includes/auth.php';
admin_require_login();

// The mail library is optional here: a problem in it must never break this diagnostic page.
$mailLibOk = false;
$mailLibError = '';
$mailLibFile = dirname(__DIR__) . '/includes/mailer.php';
if (is_file($mailLibFile)) {
    try {
        require_once $mailLibFile;
        $mailLibOk = function_exists('enw_send_mail');
        if (!$mailLibOk) $mailLibError = 'includes/mailer.php loaded but does not define enw_send_mail().';
    } catch (Throwable $t) {
        $mailLibError = $t->getMessage();
    }
} else {
    $mailLibError = 'includes/mailer.php is missing — upload the mail update.';
}

$root = dirname(__DIR__);
@set_time_limit(120);

// ============================================================ helpers

function h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** The short reason of the last PHP warning, e.g. "Permission denied". */
function sc_last_error() {
    $e = error_get_last();
    if (!$e || empty($e['message'])) {
        return 'failed (PHP gave no detail)';
    }
    $m = $e['message'];
    if (stripos($m, 'open_basedir') !== false) {
        return $m;
    }
    $pos = strrpos($m, ': ');
    return $pos === false ? $m : substr($m, $pos + 2);
}

/**
 * Run a step. The closure returns false (failure, reason taken from PHP's last
 * warning), a string starting with "!" (failure with that text), or anything
 * else (success). Returns [bool ok, string message].
 */
function sc_try($fn) {
    error_clear_last();
    try {
        $r = $fn();
    } catch (Throwable $t) {
        return [false, $t->getMessage()];
    }
    if ($r === false) {
        return [false, sc_last_error()];
    }
    if (is_string($r) && $r !== '' && $r[0] === '!') {
        return [false, substr($r, 1)];
    }
    return [true, is_string($r) ? $r : 'ok'];
}

/** "0755 owner" of a path. */
function sc_perm($path) {
    if (!file_exists($path)) {
        return 'missing';
    }
    $perm = substr(sprintf('%o', @fileperms($path)), -4);
    $uid = @fileowner($path);
    $owner = ($uid === false) ? '?' : (string) $uid;
    if ($uid !== false && function_exists('posix_getpwuid')) {
        $u = @posix_getpwuid($uid);
        if (is_array($u) && isset($u['name'])) {
            $owner = $u['name'] . ' (' . $uid . ')';
        }
    }
    return $perm . ' ' . $owner;
}

function sc_bytes($n) {
    if ($n === false || $n === null) return 'unknown';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    $n = (float) $n;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }
    return round($n, 1) . ' ' . $units[$i];
}

/** The mount a path lives on (from /proc), to spot read-only mounts. Null if unreadable. */
function sc_mount_info($path) {
    $real = @realpath($path);
    if ($real === false) return null;
    $lines = @file('/proc/self/mounts', FILE_IGNORE_NEW_LINES);
    if (!$lines) $lines = @file('/proc/mounts', FILE_IGNORE_NEW_LINES);
    if (!$lines) return null;
    $best = null;
    $bestLen = -1;
    foreach ($lines as $line) {
        $p = preg_split('/\s+/', $line);
        if (count($p) < 4) continue;
        $mp = str_replace('\040', ' ', $p[1]);
        if ($mp !== '/' && strpos($real . '/', rtrim($mp, '/') . '/') !== 0) continue;
        if (strlen($mp) > $bestLen) {
            $best = ['device' => $p[0], 'mount' => $mp, 'type' => $p[2], 'options' => $p[3],
                     'ro' => in_array('ro', explode(',', $p[3]), true)];
            $bestLen = strlen($mp);
        }
    }
    return $best;
}

/** Facts about the server, shown on every visit (nothing is written). */
function sc_env($root) {
    $env = [];
    $env['PHP version'] = PHP_VERSION . ' (' . PHP_SAPI . ')';
    $euid = function_exists('posix_geteuid') ? posix_geteuid() : null;
    $egid = function_exists('posix_getegid') ? posix_getegid() : null;
    $name = '';
    if ($euid !== null && function_exists('posix_getpwuid')) {
        $u = @posix_getpwuid($euid);
        if (is_array($u) && isset($u['name'])) $name = ' = ' . $u['name'];
    }
    $env['PHP runs as (user id / group id)'] = ($euid !== null) ? $euid . ' / ' . $egid . $name : 'unknown';
    $env['Site folder'] = $root;
    $env['Site folder owner (user id)'] = (string) @fileowner($root);
    $env['umask (rights removed from new files)'] = sprintf('%04o', umask());
    $env['open_basedir'] = ini_get('open_basedir') ?: '(not set)';
    $env['disable_functions'] = ini_get('disable_functions') ?: '(none)';
    $env['Free disk space (site)'] = sc_bytes(@disk_free_space($root));
    $env['PHP temp folder'] = sys_get_temp_dir();
    $env['upload_max_filesize / post_max_size'] = ini_get('upload_max_filesize') . ' / ' . ini_get('post_max_size');
    $m = sc_mount_info($root);
    if ($m === null) {
        $env['Mount of the site folder'] = 'not readable from PHP on this server';
    } else {
        $env['Mount of the site folder'] = $m['mount'] . ' (' . $m['type'] . ', ' . $m['options'] . ')' . ($m['ro'] ? ' — READ-ONLY' : '');
    }
    $drivers = class_exists('PDO') ? PDO::getAvailableDrivers() : [];
    $env['PDO SQLite driver'] = in_array('sqlite', $drivers, true) ? 'loaded' : 'MISSING';
    return $env;
}

// ============================================================ file tests

/**
 * Fifteen different write operations in one folder. Returns
 * [ opName => [bool|null ok, string message], ... ]. Everything is removed again.
 */
function sc_folder_battery($dir) {
    $ops = [];
    $id = 'syscheck-' . bin2hex(random_bytes(4));
    $f1 = $dir . '/' . $id . '.json';
    $f2 = $dir . '/' . $id . '-b.json';
    $f3 = $dir . '/' . $id . '-c.json';
    $fp1 = $dir . '/' . $id . '-p.json';
    $sub = $dir . '/' . $id . '-dir';
    $payload = json_encode(['syscheck' => true, 'id' => $id]);
    $skip = [null, 'skipped (an earlier step failed)'];

    // create: fopen + write (how the CMS saves)
    $ops['create'] = sc_try(function () use ($f1, $payload) {
        $fp = @fopen($f1, 'c+');
        if (!$fp) return false;
        $ok = @fwrite($fp, $payload) !== false;
        @fclose($fp);
        return $ok;
    });
    $have = ($ops['create'][0] === true);

    // put: file_put_contents on a different new file
    $ops['put'] = sc_try(function () use ($fp1, $payload) {
        return @file_put_contents($fp1, $payload, LOCK_EX);
    });

    $ops['append'] = $have ? sc_try(function () use ($f1) {
        return @file_put_contents($f1, "\n", FILE_APPEND);
    }) : $skip;

    $ops['read'] = $have ? sc_try(function () use ($f1, $payload) {
        $c = @file_get_contents($f1);
        if ($c === false) return false;
        return (strpos($c, $payload) === 0) ? true : '!content read back is different';
    }) : $skip;

    $cur = $f1;
    if ($have) {
        $ops['rename'] = sc_try(function () use ($f1, $f2) {
            return @rename($f1, $f2);
        });
        if ($ops['rename'][0] === true) $cur = $f2;
    } else {
        $ops['rename'] = $skip;
    }

    $ops['copy'] = $have ? sc_try(function () use ($cur, $f3) {
        return @copy($cur, $f3);
    }) : $skip;

    $ops['touch'] = $have ? sc_try(function () use ($cur) {
        return @touch($cur);
    }) : $skip;

    $ops['chmod'] = $have ? sc_try(function () use ($cur) {
        return @chmod($cur, 0664);
    }) : $skip;

    $ops['delete'] = $have ? sc_try(function () use ($cur, $f3) {
        $a = @unlink($cur);
        $b = file_exists($f3) ? @unlink($f3) : true;
        return ($a && $b) ? true : false;
    }) : $skip;

    $ops['mkdir'] = sc_try(function () use ($sub) {
        return @mkdir($sub, 0775);
    });
    $haveSub = ($ops['mkdir'][0] === true);

    $ops['in-dir'] = $haveSub ? sc_try(function () use ($sub, $payload) {
        return @file_put_contents($sub . '/x.json', $payload);
    }) : $skip;

    $ops['rmdir'] = $haveSub ? sc_try(function () use ($sub) {
        if (file_exists($sub . '/x.json')) @unlink($sub . '/x.json');
        return @rmdir($sub);
    }) : $skip;

    // does the file name / extension matter?
    foreach (['.php' => 'php', '.txt' => 'txt', 'no-ext' => ''] as $opName => $ext) {
        $name = $dir . '/' . $id . '-ext' . ($ext !== '' ? '.' . $ext : '');
        $ops[$opName] = sc_try(function () use ($name, $payload) {
            $r = @file_put_contents($name, $payload);
            if ($r === false) return false;
            @unlink($name);
            return true;
        });
    }

    // Leave nothing behind, whatever happened above.
    $left = @scandir($dir);
    if (is_array($left)) {
        foreach ($left as $n) {
            if (strpos($n, $id) !== 0) continue;
            $p = $dir . '/' . $n;
            if (is_dir($p)) {
                foreach (@scandir($p) ?: [] as $in) {
                    if ($in !== '.' && $in !== '..') @unlink($p . '/' . $in);
                }
                @rmdir($p);
            } else {
                @unlink($p);
            }
        }
    }
    return $ops;
}

/** Can an EXISTING file be read, and written? (Rewrites its first byte with itself: no change.) */
function sc_existing_file($path) {
    $row = ['path' => $path, 'exists' => file_exists($path), 'perm' => sc_perm($path), 'size' => null,
            'is_writable' => null, 'read' => [null, ''], 'rewrite' => [null, '']];
    if (!$row['exists']) {
        return $row;
    }
    $row['size'] = @filesize($path);
    $row['is_writable'] = @is_writable($path);
    $row['read'] = sc_try(function () use ($path) {
        return @file_get_contents($path) === false ? false : true;
    });
    $row['rewrite'] = sc_try(function () use ($path) {
        $fp = @fopen($path, 'r+');
        if (!$fp) return false;
        $b = @fread($fp, 1);
        if ($b === false || $b === '') {
            @fclose($fp);
            return 'empty file (nothing to rewrite)';
        }
        @rewind($fp);
        $w = @fwrite($fp, $b);
        @fflush($fp);
        @fclose($fp);
        return ($w === 1) ? true : false;
    });
    return $row;
}

// ============================================================ SQLite tests

function sc_pdo($path) {
    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function sc_sqlite_cleanup($path) {
    foreach (['', '-journal', '-wal', '-shm'] as $suffix) {
        if (file_exists($path . $suffix)) {
            @unlink($path . $suffix);
        }
    }
}

/** What an SQLite error message usually means, in plain words. */
function sc_sqlite_hint($msg) {
    $m = strtolower((string) $msg);
    if (strpos($m, 'readonly') !== false || strpos($m, 'read-only') !== false) {
        return 'SQLite could not write: the database file, or its FOLDER, is not writable for PHP (SQLite also creates a temporary journal file next to the database).';
    }
    if (strpos($m, 'unable to open') !== false) {
        return 'SQLite could not create or open the file (or its journal) in that folder.';
    }
    if (strpos($m, 'disk i/o') !== false) {
        return 'A low-level read/write error — typical of file systems that do not support locking properly (some network drives).';
    }
    if (strpos($m, 'locked') !== false) {
        return 'The database is locked: another process holds it, or file locking does not work on this file system.';
    }
    if (strpos($m, 'malformed') !== false || strpos($m, 'not a database') !== false) {
        return 'The database file looks damaged.';
    }
    if (strpos($m, 'no such table') !== false) {
        return 'The table does not exist (the database is empty or from another version).';
    }
    return '';
}

/**
 * A brand-new scratch database in $dir: open, create table, insert, select, delete.
 * With $mode (WAL, MEMORY, TRUNCATE ...) it first switches the journal mode.
 * Returns [stepName => [bool|null ok, message]].
 */
function sc_sqlite_scratch($dir, $mode = null) {
    $path = $dir . '/syscheck-' . bin2hex(random_bytes(4)) . '.sqlite';
    $steps = [];
    $pdo = null;

    try {
        $pdo = sc_pdo($path);
        $steps['open'] = [true, 'ok'];
    } catch (Throwable $t) {
        $steps['open'] = [false, $t->getMessage()];
        sc_sqlite_cleanup($path);
        return $steps;
    }

    $run = function ($name, $fn) use (&$steps, &$pdo) {
        foreach ($steps as $s) {
            if ($s[0] === false) {
                $steps[$name] = [null, 'skipped'];
                return;
            }
        }
        try {
            $r = $fn($pdo);
            $steps[$name] = [true, is_string($r) ? $r : 'ok'];
        } catch (Throwable $t) {
            $steps[$name] = [false, $t->getMessage()];
        }
    };

    if ($mode !== null) {
        $run('journal ' . $mode, function ($p) use ($mode) {
            $now = strtolower((string) $p->query('PRAGMA journal_mode=' . $mode)->fetchColumn());
            if ($now !== strtolower($mode)) {
                return '!the mode stayed "' . $now . '"';
            }
            return 'mode is ' . $now;
        });
    }
    $run('create table', function ($p) { $p->exec('CREATE TABLE t (id INTEGER PRIMARY KEY, v TEXT)'); });
    $run('insert', function ($p) { $p->exec("INSERT INTO t (v) VALUES ('hello')"); });
    $run('select', function ($p) {
        $v = $p->query('SELECT v FROM t')->fetchColumn();
        return ($v === 'hello') ? true : '!read back a different value';
    });
    $run('delete row', function ($p) { $p->exec('DELETE FROM t'); });

    $pdo = null;
    sc_sqlite_cleanup($path);
    return $steps;
}

/** Tests on the REAL database. Returns a list of [step, bool|null ok, message]. */
function sc_sqlite_real($dbPath, $doCommit) {
    $steps = [];
    $add = function ($name, $ok, $msg) use (&$steps) {
        $steps[] = [$name, $ok, $msg];
    };

    if (!is_file($dbPath)) {
        $add('database file', false, 'data/admin.sqlite does not exist');
        return $steps;
    }
    try {
        $pdo = sc_pdo($dbPath);
        $add('open the database', true, 'ok');
    } catch (Throwable $t) {
        $add('open the database', false, $t->getMessage());
        return $steps;
    }

    try {
        $add('read (count users)', true, $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() . ' user(s)');
    } catch (Throwable $t) {
        $add('read (count users)', false, $t->getMessage());
    }
    try {
        $r = (string) $pdo->query('PRAGMA integrity_check')->fetchColumn();
        $add('integrity check (before)', $r === 'ok', $r);
    } catch (Throwable $t) {
        $add('integrity check (before)', false, $t->getMessage());
    }
    try {
        $add('journal mode', true, (string) $pdo->query('PRAGMA journal_mode')->fetchColumn());
    } catch (Throwable $t) {
        $add('journal mode', false, $t->getMessage());
    }

    $tbl = 'syscheck_' . bin2hex(random_bytes(4));

    // A write that is rolled back: needs the lock and the journal file, keeps nothing.
    try {
        $pdo->exec('BEGIN IMMEDIATE');
        $pdo->exec("CREATE TABLE $tbl (id INTEGER PRIMARY KEY, v TEXT)");
        $pdo->exec("INSERT INTO $tbl (v) VALUES ('x')");
        $pdo->exec('ROLLBACK');
        $add('write test, rolled back (nothing is kept)', true, 'ok');
    } catch (Throwable $t) {
        try { $pdo->exec('ROLLBACK'); } catch (Throwable $t2) { /* no transaction was open */ }
        $add('write test, rolled back (nothing is kept)', false, $t->getMessage());
    }

    // A real commit, exactly like creating a user: a temporary table is created, then dropped.
    if ($doCommit) {
        $created = false;
        try {
            $pdo->exec('BEGIN IMMEDIATE');
            $pdo->exec("CREATE TABLE $tbl (id INTEGER PRIMARY KEY, v TEXT)");
            $pdo->exec("INSERT INTO $tbl (v) VALUES ('x')");
            $pdo->exec('COMMIT');
            $created = true;
            $add('write test, committed (temporary table)', true, 'ok');
        } catch (Throwable $t) {
            try { $pdo->exec('ROLLBACK'); } catch (Throwable $t2) { /* nothing open */ }
            $add('write test, committed (temporary table)', false, $t->getMessage());
        }
        if ($created) {
            try {
                $pdo->exec("DROP TABLE $tbl");
                $add('clean up (drop the temporary table)', true, 'ok');
            } catch (Throwable $t) {
                $add('clean up (drop the temporary table)', false, $t->getMessage() . ' — a leftover empty table named ' . $tbl . ' can be ignored.');
            }
            try {
                $r = (string) $pdo->query('PRAGMA integrity_check')->fetchColumn();
                $add('integrity check (after)', $r === 'ok', $r);
            } catch (Throwable $t) {
                $add('integrity check (after)', false, $t->getMessage());
            }
        }
    } else {
        $add('write test, committed (temporary table)', null, 'skipped (you unticked it)');
    }
    return $steps;
}

// ============================================================ the whole run

function sc_run_all($root, $doCommit) {
    $rep = ['started' => date('Y-m-d H:i:s'), 'folders' => [], 'files' => [], 'sqlite' => [], 'conclusions' => []];

    $sess = preg_replace('/^\d+;(?:\d+;)?/', '', (string) ini_get('session.save_path'));
    $candidates = [
        ['Site root',               $root,                    'the public site',                 'site'],
        ['includes/',               $root . '/includes',      'PHP code',                        'site'],
        ['admin/',                  $root . '/admin',         'admin code',                      'site'],
        ['assets/',                 $root . '/assets',        'css / js / images',               'site'],
        ['assets/img/',             $root . '/assets/img',    'IMAGE UPLOADS',                   'site'],
        ['content/',                $root . '/content',       'CONTENT (JSON texts)',            'site'],
        ['text/',                   $root . '/text',          'new content folder (optional)',   'site'],
        ['text/backups/',           $root . '/text/backups',  'backups, new content folder',     'site'],
        ['data/',                   $root . '/data',          'SQLITE (admin users)',            'site'],
        ['data/backups/',           $root . '/data/backups',  'BACKUPS before each save',        'site'],
        ['tmp/',                    $root . '/tmp',           '(optional folder)',               'site'],
        ['fr/',                     $root . '/fr',            'old language folder',             'site'],
        ['Folder above the site',   dirname($root),           'outside the public site folder',  'above'],
        ['PHP temp folder',         sys_get_temp_dir(),       'outside the web space',           'temp'],
        ['/tmp',                    '/tmp',                   'outside the web space',           'temp'],
        ['/var/tmp',                '/var/tmp',               'outside the web space',           'temp'],
        ['PHP session folder',      $sess,                    'outside the web space',           'temp'],
    ];

    $seen = [];
    foreach ($candidates as $c) {
        list($label, $path, $used, $zone) = $c;
        $row = ['label' => $label, 'path' => $path, 'used' => $used, 'zone' => $zone,
                'exists' => false, 'perm' => '', 'ops' => []];
        if ($path === '' || !is_dir($path)) {
            $rep['folders'][] = $row;
            continue;
        }
        $real = @realpath($path);
        $key = $real !== false ? $real : $path;
        if (isset($seen[$key])) {
            continue;   // same folder as one already tested
        }
        $seen[$key] = true;
        $row['exists'] = true;
        $row['perm'] = sc_perm($path);
        $row['ops'] = sc_folder_battery($path);
        $rep['folders'][] = $row;
    }

    // Existing files the CMS writes to.
    $files = [];
    foreach (['content', 'text'] as $d) {
        foreach (glob($root . '/' . $d . '/*.json') ?: [] as $p) $files[] = $p;
    }
    $files[] = $root . '/data/admin.sqlite';
    $files[] = $root . '/data/admin-users.php';
    foreach ($files as $p) {
        $rep['files'][] = sc_existing_file($p);
    }

    // SQLite.
    $drivers = class_exists('PDO') ? PDO::getAvailableDrivers() : [];
    $sq = ['available' => in_array('sqlite', $drivers, true), 'version' => '', 'memory' => null, 'real' => [], 'scratch' => []];
    if ($sq['available']) {
        try {
            $m = sc_pdo(':memory:');
            $sq['version'] = (string) $m->query('SELECT sqlite_version()')->fetchColumn();
            $sq['memory'] = [true, 'ok'];
        } catch (Throwable $t) {
            $sq['memory'] = [false, $t->getMessage()];
        }
        $sq['real'] = sc_sqlite_real($root . '/data/admin.sqlite', $doCommit);
        foreach ($rep['folders'] as $f) {
            if (!$f['exists']) continue;
            $entry = ['label' => $f['label'], 'path' => $f['path'], 'zone' => $f['zone'], 'steps' => sc_sqlite_scratch($f['path']), 'modes' => []];
            $allOk = true;
            foreach ($entry['steps'] as $s) {
                if ($s[0] !== true) $allOk = false;
            }
            if ($allOk) {
                foreach (['WAL', 'MEMORY', 'TRUNCATE'] as $mode) {
                    $entry['modes'][$mode] = sc_sqlite_scratch($f['path'], $mode);
                }
            }
            $sq['scratch'][] = $entry;
        }
    }
    $rep['sqlite'] = $sq;

    $rep['conclusions'] = sc_conclusions($rep);
    return $rep;
}

// ============================================================ interpretation

function sc_error_kind($msg) {
    $m = strtolower((string) $msg);
    if (strpos($m, 'read-only file system') !== false) return 'readonly';
    if (strpos($m, 'open_basedir') !== false) return 'basedir';
    if (strpos($m, 'permission denied') !== false || strpos($m, 'operation not permitted') !== false) return 'denied';
    if (strpos($m, 'no space') !== false || strpos($m, 'quota') !== false) return 'space';
    return 'other';
}

function sc_step_ok($ops, $names) {
    foreach ($names as $n) {
        if (!isset($ops[$n]) || $ops[$n][0] !== true) return false;
    }
    return true;
}

/** Plain-language findings, each [level (ok|warn|bad|info), text]. */
function sc_conclusions($rep) {
    $out = [];
    $site = [];
    $siteOk = [];
    $tempOk = [];
    $aboveOk = false;
    $errs = [];

    foreach ($rep['folders'] as $f) {
        if (!$f['exists']) continue;
        $createOk = isset($f['ops']['create']) && $f['ops']['create'][0] === true;
        if ($f['zone'] === 'site') {
            $site[] = $f;
            if ($createOk) $siteOk[] = $f;
        } elseif ($f['zone'] === 'temp' && $createOk) {
            $tempOk[] = $f;
        } elseif ($f['zone'] === 'above' && $createOk) {
            $aboveOk = true;
        }
        foreach ($f['ops'] as $name => $r) {
            if ($name !== 'chmod' && $r[0] === false) $errs[] = $r[1];
        }
    }

    $topErr = '';
    if ($errs) {
        $counts = array_count_values($errs);
        arsort($counts);
        $keys = array_keys($counts);
        $topErr = (string) $keys[0];
    }
    $kind = sc_error_kind($topErr);

    // 1. The big picture.
    if ($site && !$siteOk) {
        if ($tempOk) {
            $why = 'it can in the temp folder, so PHP itself works fine';
        } else {
            $why = 'it cannot in the temp folders either';
        }
        $txt = 'PHP cannot create a file in ANY folder of your web space (' . $why . '). The most common error was "' . $topErr . '".';
        if ($kind === 'readonly') {
            $txt .= ' "Read-only file system" means the web space is mounted read-only for PHP.';
        } elseif ($kind === 'denied') {
            $txt .= ' The server refuses the write although the rights may be fully open, so this is a restriction set by the host (write protection or a PHP sandbox) — chmod / chown cannot fix it; look in the hosting control panel or ask support.';
        } elseif ($kind === 'basedir') {
            $txt .= ' PHP\'s open_basedir setting restricts where scripts may write.';
        } elseif ($kind === 'space') {
            $txt .= ' The disk space or quota of the account seems to be used up.';
        }
        $out[] = ['bad', $txt];
    } elseif ($site && count($siteOk) < count($site)) {
        $okNames = array_map(function ($f) { return $f['label']; }, $siteOk);
        $badNames = [];
        foreach ($site as $f) {
            if (!in_array($f, $siteOk, true)) $badNames[] = $f['label'];
        }
        $out[] = ['warn', 'PHP can write in: ' . implode(', ', $okNames) . '. It cannot in: ' . implode(', ', $badNames) . '. Most common error: "' . $topErr . '".'];
    } elseif ($site) {
        $out[] = ['ok', 'PHP can create files in every folder of the site.'];
    }

    // 2. Can the CMS save where it needs to?
    $needed = ['content/' => 'content texts', 'text/' => 'content texts (new folder)', 'data/' => 'the users database',
               'data/backups/' => 'backups', 'assets/img/' => 'image uploads'];
    $lines = [];
    foreach ($rep['folders'] as $f) {
        if (!$f['exists'] || !isset($needed[$f['label']])) continue;
        $good = sc_step_ok($f['ops'], ['create', 'put', 'rename', 'delete']);
        $lines[] = ($good ? '✅ ' : '❌ ') . $f['label'] . ' (' . $needed[$f['label']] . ')';
    }
    if ($lines) {
        $allGood = (strpos(implode('', $lines), '❌') === false);
        $out[] = [$allGood ? 'ok' : 'bad', 'Saving, the way the CMS does it (create, write, rename, delete): ' . implode('   ', $lines)];
    }

    // 3. A writable folder to move things to.
    if ($site && !$siteOk && $aboveOk) {
        $out[] = ['info', 'The folder ABOVE the site folder is writable. The CMS data could be kept there (it is also safer, because it would not be inside the public folder).'];
    }
    if ($site && !$siteOk && $tempOk && !$aboveOk) {
        $out[] = ['info', 'Only temp folders (' . implode(', ', array_map(function ($f) { return $f['path']; }, $tempOk)) . ') are writable. They are temporary and may be emptied at any time, so content must NOT be kept there.'];
    }

    // 4. File-type effects, per folder.
    foreach ($rep['folders'] as $f) {
        if (!$f['exists']) continue;
        $json = isset($f['ops']['create']) && $f['ops']['create'][0] === true;
        $php = isset($f['ops']['.php']) && $f['ops']['.php'][0] === true;
        if ($json && !$php) {
            $out[] = ['warn', 'In ' . $f['label'] . ' ordinary files can be created but .php files cannot — the host blocks new PHP files there.'];
        }
    }
    $chmodAny = false;
    $chmodBad = false;
    foreach ($rep['folders'] as $f) {
        if (!$f['exists'] || !isset($f['ops']['chmod'])) continue;
        if ($f['ops']['chmod'][0] === true) $chmodAny = true;
        if ($f['ops']['chmod'][0] === false) $chmodBad = true;
    }
    if ($chmodBad && !$chmodAny) {
        $out[] = ['info', 'PHP is not allowed to change file rights (chmod) anywhere. That is common and harmless — it only means a "fix the rights from PHP" button could not work.'];
    }

    // 5. Existing files.
    $badFiles = [];
    foreach ($rep['files'] as $fl) {
        if ($fl['exists'] && $fl['rewrite'][0] === false) $badFiles[] = basename($fl['path']);
    }
    if ($badFiles) {
        $out[] = ['bad', 'Existing files that cannot be written: ' . implode(', ', $badFiles) . '. Saving in the admin would fail for these.'];
    } elseif ($rep['files']) {
        $anyExisting = false;
        foreach ($rep['files'] as $fl) {
            if ($fl['exists']) $anyExisting = true;
        }
        if ($anyExisting) $out[] = ['ok', 'All existing content / database files can be written.'];
    }

    // 6. SQLite.
    $sq = $rep['sqlite'];
    if (!$sq['available']) {
        $out[] = ['bad', 'The PDO SQLite driver is not available — the admin login cannot work without it.'];
    } else {
        $realFail = null;
        foreach ($sq['real'] as $s) {
            if ($s[1] === false) { $realFail = $s; break; }
        }
        if ($realFail !== null) {
            $hint = sc_sqlite_hint($realFail[2]);
            $out[] = ['bad', 'Your real database (data/admin.sqlite): the step "' . $realFail[0] . '" failed with "' . $realFail[2] . '".' . ($hint !== '' ? ' ' . $hint : '')];
        } elseif ($sq['real']) {
            $out[] = ['ok', 'Your real database (data/admin.sqlite) can be read and written.'];
        }
        foreach ($sq['scratch'] as $e) {
            $fail = null;
            foreach ($e['steps'] as $name => $s) {
                if ($s[0] === false) { $fail = [$name, $s[1]]; break; }
            }
            if ($fail === null) continue;
            // is it only SQLite that fails, while plain files work in the same folder?
            $plainOk = false;
            foreach ($rep['folders'] as $f) {
                if ($f['label'] === $e['label'] && isset($f['ops']['create']) && $f['ops']['create'][0] === true) $plainOk = true;
            }
            if ($plainOk) {
                $hint = sc_sqlite_hint($fail[1]);
                $out[] = ['warn', 'SQLite fails in ' . $e['label'] . ' (step "' . $fail[0] . '": ' . $fail[1] . ') although ordinary files can be created there — an SQLite-specific problem.' . ($hint !== '' ? ' ' . $hint : '')];
            }
        }
        $sqliteTempOnly = false;
        $sqliteSiteOk = false;
        foreach ($sq['scratch'] as $e) {
            $allOk = true;
            foreach ($e['steps'] as $s) {
                if ($s[0] !== true) $allOk = false;
            }
            if ($allOk && $e['zone'] === 'site') $sqliteSiteOk = true;
            if ($allOk && $e['zone'] === 'temp') $sqliteTempOnly = true;
        }
        if ($sqliteTempOnly && !$sqliteSiteOk) {
            $out[] = ['info', 'A new SQLite database can only be created in the temp folders, not inside the site.'];
        }
    }

    return $out;
}

// ============================================================ plain-text report

function sc_cell_text($r) {
    if ($r[0] === true) return 'OK';
    if ($r[0] === null) return '-';
    return 'FAIL: ' . $r[1];
}

function sc_report_text($rep, $env) {
    $t = [];
    $t[] = 'ENWIRES SYSTEM CHECK — ' . $rep['started'];
    $t[] = '';
    $t[] = '== SERVER ==';
    foreach ($env as $k => $v) $t[] = $k . ': ' . $v;
    $t[] = '';
    $t[] = '== WHAT THIS MEANS ==';
    foreach ($rep['conclusions'] as $c) $t[] = '[' . strtoupper($c[0]) . '] ' . $c[1];
    $t[] = '';
    $t[] = '== FOLDERS (15 operations each; only failures are listed) ==';
    foreach ($rep['folders'] as $f) {
        if (!$f['exists']) {
            $t[] = $f['label'] . ' — ' . $f['path'] . ' — does not exist';
            continue;
        }
        $fails = [];
        $okCount = 0;
        foreach ($f['ops'] as $name => $r) {
            if ($r[0] === true) $okCount++;
            if ($r[0] === false) $fails[] = $name . ' = ' . $r[1];
        }
        $t[] = $f['label'] . ' — ' . $f['path'] . ' — rights ' . $f['perm'] . ' — ' . $okCount . '/' . count($f['ops']) . ' OK' . ($fails ? ' — FAILED: ' . implode('; ', $fails) : '');
    }
    $t[] = '';
    $t[] = '== EXISTING FILES ==';
    foreach ($rep['files'] as $fl) {
        if (!$fl['exists']) {
            $t[] = $fl['path'] . ' — missing';
            continue;
        }
        $t[] = $fl['path'] . ' — rights ' . $fl['perm'] . ' — is_writable: ' . ($fl['is_writable'] ? 'yes' : 'no') . ' — read: ' . sc_cell_text($fl['read']) . ' — rewrite: ' . sc_cell_text($fl['rewrite']);
    }
    $t[] = '';
    $t[] = '== SQLITE ==';
    $sq = $rep['sqlite'];
    if (!$sq['available']) {
        $t[] = 'PDO SQLite driver: MISSING';
    } else {
        $t[] = 'SQLite version: ' . $sq['version'] . ' — in-memory test: ' . ($sq['memory'] ? sc_cell_text($sq['memory']) : '-');
        $t[] = 'Real database data/admin.sqlite:';
        foreach ($sq['real'] as $s) $t[] = '  ' . $s[0] . ': ' . ($s[1] === true ? 'OK — ' . $s[2] : ($s[1] === null ? '- ' . $s[2] : 'FAIL: ' . $s[2]));
        $t[] = 'New scratch databases:';
        foreach ($sq['scratch'] as $e) {
            $fails = [];
            foreach ($e['steps'] as $name => $s) {
                if ($s[0] === false) $fails[] = $name . ' = ' . $s[1];
            }
            $modeBits = [];
            foreach ($e['modes'] as $mode => $steps) {
                $bad = [];
                foreach ($steps as $name => $s) {
                    if ($s[0] === false) $bad[] = $s[1];
                }
                $modeBits[] = $mode . ($bad ? ' FAIL (' . $bad[0] . ')' : ' OK');
            }
            $t[] = '  ' . $e['label'] . ': ' . ($fails ? 'FAIL — ' . implode('; ', $fails) : 'OK') . ($modeBits ? ' — journal modes: ' . implode(', ', $modeBits) : '');
        }
    }
    return implode("\n", $t);
}

// ============================================================ mail check

/** Can this server open an outgoing TCP connection to host:port? */
function sc_tcp_check($host, $port, $timeout = 5) {
    $t = microtime(true);
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, $timeout);
    $ms = (int) round((microtime(true) - $t) * 1000);
    if ($fp) {
        @fclose($fp);
        return [true, 'connected in ' . $ms . ' ms'];
    }
    return [false, ($errstr !== '' ? $errstr : 'no connection') . ' (' . $errno . ') after ' . $ms . ' ms'];
}

/** How mail is set up in PHP on this server (nothing is sent). */
function sc_mail_env() {
    $dis = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    return [
        'PHP mail() available'                    => (function_exists('mail') && !in_array('mail', $dis, true)) ? 'yes' : 'NO — disabled on this server',
        'sendmail_path'                           => ini_get('sendmail_path') ?: '(not set)',
        'SMTP / smtp_port (used on Windows only)' => (string) ini_get('SMTP') . ' / ' . (string) ini_get('smtp_port'),
        'sendmail_from'                           => ini_get('sendmail_from') ?: '(not set)',
        'mail.add_x_header'                       => ini_get('mail.add_x_header') ? 'on' : 'off',
        'mail.log'                                => ini_get('mail.log') ?: '(not set)',
        'Can open network connections (SMTP)'     => function_exists('stream_socket_client') ? 'yes' : 'NO — stream_socket_client() is disabled',
        'OpenSSL (needed for encrypted SMTP)'     => extension_loaded('openssl') ? 'yes' : 'NO',
    ];
}

function sc_dns_txt($name) {
    $recs = @dns_get_record($name, DNS_TXT);
    $out = [];
    if (is_array($recs)) {
        foreach ($recs as $r) {
            if (isset($r['txt'])) $out[] = $r['txt'];
        }
    }
    return $out;
}

/** MX / SPF / DMARC of the sender's domain: what receiving mail servers look at. */
function sc_mail_dns($domain) {
    $info = ['domain' => $domain, 'available' => function_exists('dns_get_record'), 'mx' => [], 'spf' => [], 'dmarc' => []];
    if (!$info['available'] || $domain === '') {
        return $info;
    }
    $mx = @dns_get_record($domain, DNS_MX);
    if (is_array($mx)) {
        foreach ($mx as $r) {
            $info['mx'][] = ($r['target'] ?? '?') . ' (priority ' . ($r['pri'] ?? '?') . ')';
        }
    }
    foreach (sc_dns_txt($domain) as $t) {
        if (stripos($t, 'v=spf1') === 0) $info['spf'][] = $t;
    }
    foreach (sc_dns_txt('_dmarc.' . $domain) as $t) {
        if (stripos($t, 'v=DMARC1') === 0) $info['dmarc'][] = $t;
    }
    return $info;
}

/** Plain-language advice from what happened. Each item: [level, text]. */
function sc_mail_hints($res, $tcp, $dns) {
    $h = [];
    $e = strtolower((string) $res['error']);

    if ($res['ok']) {
        if ($res['transport'] === 'smtp') {
            $h[] = ['ok', 'The mail server accepted the message (its answer is in the conversation below). Now check the recipient\'s inbox and spam folder. You can use these settings on the site: copy the snippet at the bottom into includes/mail-config.php.'];
        } else {
            $h[] = ['warn', 'mail() only proves that THIS server\'s own mail system accepted the message — it cannot tell whether the message gets delivered. Wait a few minutes and check the inbox AND spam. If nothing arrives, mail() does not work on this host: use SMTP (your host\'s mail server, or a provider like Brevo / Gmail / Mailjet) — SMTP reports exactly what happens.'];
        }
    } else {
        if (strpos($e, 'not a valid email') !== false) {
            $h[] = ['bad', 'Check the recipient address — it is not a valid email address.'];
        } elseif (strpos($e, 'mail() is disabled') !== false) {
            $h[] = ['bad', 'The host switched mail() off. Only SMTP can work here.'];
        } elseif (strpos($e, 'mail() was refused') !== false) {
            $h[] = ['bad', 'The server\'s own mail system rejected the message. Try again with/without "envelope sender (-f)" and with a From address that is a real mailbox of your domain. If it keeps failing, use SMTP.'];
        } elseif (strpos($e, 'could not connect') !== false || strpos($e, 'did not answer') !== false) {
            $h[] = ['bad', 'The connection to the mail server failed. Check the host name and port, and see "Outgoing connections" below: if every port fails, the host blocks outgoing mail connections.'];
        } elseif (strpos($e, 'login failed') !== false) {
            $h[] = ['bad', 'The mail server rejected the username/password. Most providers want the FULL email address as username; Gmail / Microsoft need an "app password", not your normal password.'];
        } elseif (strpos($e, 'refused the sender') !== false) {
            $h[] = ['bad', 'The server does not accept this From address. Set the From address to a real mailbox on that mail account (usually the same as the SMTP username).'];
        } elseif (strpos($e, 'refused the recipient') !== false) {
            $h[] = ['bad', 'The server refused the recipient. Either the address does not exist, or the server only relays mail for logged-in users — set the SMTP username and password.'];
        } elseif (strpos($e, 'tls') !== false || strpos($e, 'starttls') !== false || strpos($e, 'certificate') !== false) {
            $h[] = ['bad', 'The encrypted connection failed. Try security "ssl" with port 465, or untick "verify certificate" (less secure).'];
        } elseif (strpos($e, 'offer login') !== false) {
            $h[] = ['bad', 'Login is only offered after encryption: choose security "tls" (port 587) or "ssl" (port 465).'];
        } else {
            $h[] = ['bad', 'The message was not accepted. The exact reason is in the error line and the conversation below.'];
        }
    }

    if ($tcp) {
        $any = false;
        foreach ($tcp as $r) {
            if ($r[1]) $any = true;
        }
        if (!$any) {
            $h[] = ['bad', 'This server could not open an outgoing connection to the mail host on ANY port tried. Outgoing SMTP is most likely blocked by the host: use the host\'s own SMTP server (ask support for its address and the allowed port) or a mail provider that offers port 587 / 465 / 2525.'];
        }
    }

    if ($dns !== null && $dns['available'] && $dns['domain'] !== '') {
        if (!$dns['mx']) {
            $h[] = ['warn', 'The sender domain ' . $dns['domain'] . ' has no MX record. Many receiving servers reject mail from a domain that cannot receive mail.'];
        }
        if (!$dns['spf']) {
            $h[] = ['warn', 'The sender domain ' . $dns['domain'] . ' has no SPF record, so mail sent from this server is likely to land in spam or be rejected. Add an SPF TXT record that includes the server or provider you send through.'];
        }
        if (!$dns['dmarc']) {
            $h[] = ['info', 'No DMARC record for ' . $dns['domain'] . '. Not required, but Gmail and Yahoo expect SPF + DKIM (+ DMARC) for reliable delivery.'];
        }
    }
    return $h;
}

/** A ready-to-paste mail-config.php for the settings just tested. */
function sc_mail_snippet($used) {
    $q = function ($v) { return var_export((string) $v, true); };
    $lines = ["<?php", "return ["];
    $lines[] = "    'transport'       => " . $q($used['transport']) . ",";
    $lines[] = "    'from_address'    => " . $q($used['from_typed']) . ",";
    $lines[] = "    'from_name'       => 'Enwires Website',";
    $lines[] = "    'envelope_sender' => " . ($used['envelope'] ? 'true' : 'false') . ",";
    if ($used['transport'] === 'smtp') {
        $lines[] = "    'smtp' => [";
        $lines[] = "        'host'        => " . $q($used['smtp']['host']) . ",";
        $lines[] = "        'port'        => " . (int) $used['smtp']['port'] . ",";
        $lines[] = "        'secure'      => " . $q($used['smtp']['secure']) . ",";
        $lines[] = "        'username'    => " . $q($used['smtp']['username']) . ",";
        $lines[] = "        'password'    => 'YOUR-PASSWORD',";
        $lines[] = "        'timeout'     => 15,";
        $lines[] = "        'verify_peer' => " . ($used['smtp']['verify_peer'] ? 'true' : 'false') . ",";
        $lines[] = "    ],";
    }
    $lines[] = "];";
    return implode("\n", $lines);
}

/** Runs one mail test from the form. Never throws. */
function sc_mail_run($post) {
    $m = ['error' => '', 'result' => null, 'tcp' => [], 'dns' => null, 'hints' => [], 'used' => [], 'env' => sc_mail_env(), 'started' => date('Y-m-d H:i:s')];
    if (!function_exists('enw_send_mail')) {
        $m['error'] = 'The mail library could not be loaded.';
        return $m;
    }
    $cfg = enw_mail_config();

    $to = trim((string) ($post['mail_to'] ?? ''));
    $choice = (string) ($post['mail_transport'] ?? 'config');
    $from = trim((string) ($post['mail_from'] ?? ''));

    $opt = ['from_name' => 'Enwires mail test', 'envelope_sender' => !empty($post['mail_envelope'])];
    if ($choice === 'mail' || $choice === 'smtp') $opt['transport'] = $choice;
    if ($from !== '') $opt['from_address'] = $from;

    $secure = (string) ($post['smtp_secure'] ?? 'tls');
    $smtp = [
        'host'        => trim((string) ($post['smtp_host'] ?? '')),
        'port'        => (int) ($post['smtp_port'] ?? 587),
        'secure'      => in_array($secure, ['tls', 'ssl', 'none'], true) ? $secure : 'tls',
        'username'    => trim((string) ($post['smtp_username'] ?? '')),
        'verify_peer' => !empty($post['smtp_verify']),
    ];
    $pw = (string) ($post['smtp_password'] ?? '');
    if ($pw !== '') $smtp['password'] = $pw;
    $opt['smtp'] = $smtp;

    $effective = $opt['transport'] ?? $cfg['transport'];
    $m['used'] = [
        'transport'  => $effective,
        'from_typed' => $from,
        'envelope'   => !empty($opt['envelope_sender']),
        'smtp'       => $smtp,
        'choice'     => $choice,
    ];

    // Can this server reach the mail host at all?
    if ($effective === 'smtp' && $smtp['host'] !== '') {
        $ports = array_values(array_unique([(int) $smtp['port'], 587, 465, 25]));
        foreach ($ports as $port) {
            list($ok, $msg) = sc_tcp_check($smtp['host'], $port);
            $m['tcp'][] = [$smtp['host'] . ':' . $port, $ok, $msg];
        }
    }

    $subject = 'Enwires mail test ' . date('Y-m-d H:i:s');
    $body = "This is a test email from the Enwires admin (System check -> Mail check).\n\n"
          . "If you can read this, the site can send email with these settings.\n\n"
          . "Transport:  " . $effective . "\n"
          . "Sent at:    " . date('c') . "\n"
          . "Server:     " . ($_SERVER['SERVER_NAME'] ?? 'unknown') . "\n";

    $res = enw_send_mail($to, $subject, $body, $opt);
    $m['result'] = $res;

    $domain = (strpos($res['from'], '@') !== false) ? substr(strrchr($res['from'], '@'), 1) : '';
    $m['dns'] = sc_mail_dns($domain);
    $m['hints'] = sc_mail_hints($res, $m['tcp'], $m['dns']);
    return $m;
}

function sc_mail_text($m) {
    $t = [];
    $t[] = 'ENWIRES MAIL CHECK — ' . $m['started'];
    if ($m['error'] !== '') {
        $t[] = 'ERROR: ' . $m['error'];
        return implode("\n", $t);
    }
    $r = $m['result'];
    $t[] = 'Result: ' . ($r['ok'] ? 'ACCEPTED' : 'FAILED') . ' — transport ' . $r['transport'] . ' — from ' . $r['from'] . ' — to ' . $r['to'];
    if ($r['error'] !== '') $t[] = 'Error: ' . $r['error'];
    foreach ($m['hints'] as $h) $t[] = '[' . strtoupper($h[0]) . '] ' . $h[1];
    $t[] = '';
    $t[] = '== OUTGOING CONNECTIONS ==';
    foreach ($m['tcp'] as $c) $t[] = $c[0] . ': ' . ($c[1] ? 'OK' : 'FAIL') . ' — ' . $c[2];
    if ($m['dns'] !== null) {
        $t[] = '';
        $t[] = '== DNS of ' . $m['dns']['domain'] . ' ==';
        $t[] = 'MX: ' . ($m['dns']['mx'] ? implode(' | ', $m['dns']['mx']) : 'none');
        $t[] = 'SPF: ' . ($m['dns']['spf'] ? implode(' | ', $m['dns']['spf']) : 'none');
        $t[] = 'DMARC: ' . ($m['dns']['dmarc'] ? implode(' | ', $m['dns']['dmarc']) : 'none');
    }
    $t[] = '';
    $t[] = '== CONVERSATION ==';
    foreach ($r['log'] as $line) $t[] = $line;
    $t[] = '';
    $t[] = '== PHP MAIL SETUP ==';
    foreach ($m['env'] as $k => $v) $t[] = $k . ': ' . $v;
    return implode("\n", $t);
}

// ============================================================ run (POST only)

$report = null;
$csrfFailed = false;
$doCommit = true;
$plain = '';

$env = sc_env($root);

$mail = null;
$mailPlain = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf()) {
        $csrfFailed = true;
    } elseif (($_POST['action'] ?? '') === 'mail_test') {
        $mail = sc_mail_run($_POST);
        $mailPlain = sc_mail_text($mail);
    } else {
        $doCommit = !empty($_POST['commit']);
        $report = sc_run_all($root, $doCommit);
        $plain = sc_report_text($report, $env);
    }
}

// Form values: what was just posted, else the saved mail settings.
$mcfg = $mailLibOk ? enw_mail_config() : sc_mail_defaults_fallback();
function sc_mail_defaults_fallback() {
    return ['transport' => 'mail', 'envelope_sender' => false,
            'smtp' => ['host' => '', 'port' => 587, 'secure' => 'tls', 'username' => '', 'password' => '', 'verify_peer' => true]];
}
$posted = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mail_test');
$mv = function ($key, $default) use ($posted) {
    return ($posted && isset($_POST[$key])) ? (string) $_POST[$key] : (string) $default;
};
$defaultTo = '';
if ($mailLibOk && function_exists('enw_layout_shared')) {
    $defaultTo = (string) enw_layout_shared()['contact_recipient'];
}
$autoFrom = ($mailLibOk && function_exists('enw_mail_pick_from')) ? enw_mail_pick_from($mcfg, '') : '';

// ============================================================ page

function sc_cell($r) {
    if ($r[0] === true) return '<td class="sc-c sc-ok" title="' . h($r[1]) . '">✅</td>';
    if ($r[0] === null) return '<td class="sc-c sc-skip" title="' . h($r[1]) . '">·</td>';
    return '<td class="sc-c sc-bad" title="' . h($r[1]) . '">❌</td>';
}

$pageTitle = 'System check';
require __DIR__ . '/includes/layout-header.php';
?>
<style>
  .sc-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
  .sc-table th { text-align: left; padding: 6px 6px; border-bottom: 2px solid var(--line); font-weight: 600; white-space: nowrap; }
  .sc-table td { padding: 6px 6px; border-bottom: 1px solid var(--line); vertical-align: top; }
  .sc-table td.sc-c { text-align: center; width: 40px; }
  .sc-ok { background: #eaf7ee; }
  .sc-bad { background: #fdecec; }
  .sc-skip { background: #f3f3f5; color: #999; }
  .sc-note { font-size: .78rem; color: var(--ink-dim); }
  .sc-find { padding: 10px 14px; border-radius: 6px; margin-bottom: 8px; border-left: 5px solid #bbb; background: #f6f6f8; }
  .sc-find.ok { border-color: #2e9e57; background: #eaf7ee; }
  .sc-find.warn { border-color: #e0a100; background: #fff8e1; }
  .sc-find.bad { border-color: #d33; background: #fdecec; }
  .sc-find.info { border-color: #4da3ff; background: #eef6ff; }
  .sc-scroll { overflow-x: auto; }
  .sc-report { width: 100%; height: 260px; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: .78rem; }
  .sc-kv td:first-child { color: var(--ink-dim); padding-right: 18px; white-space: nowrap; }
  .sc-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 4px 16px; margin-bottom: 8px; }
  .sc-grid input[type="text"], .sc-grid input[type="email"], .sc-grid input[type="password"], .sc-grid select { width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid var(--line); border-radius: 4px; font: inherit; background: #fff; }
  .sc-pre { background: #1e1e24; color: #e6e6ea; padding: 12px 14px; border-radius: 6px; overflow: auto; font-size: .8rem; line-height: 1.5; max-height: 420px; }
</style>

<a href="index.php" class="back-link">&larr; Back to dashboard</a>
<h1>System check</h1>
<p class="subtitle">Finds out exactly what happens when PHP tries to write — in files, in folders and in SQLite.</p>

<?php if ($csrfFailed): ?>
  <div class="alert alert-error">Your session expired — please try again.</div>
<?php endif; ?>

<p class="sc-note" style="margin-top:0;"><a href="#mail">Mail check</a> &nbsp;·&nbsp; <a href="#files">File &amp; SQLite check</a></p>

<div class="panel" id="mail">
  <h2 style="margin-top:0;">Mail check</h2>
  <?php if (!$mailLibOk): ?>
    <div class="sc-find bad">The mail library could not be loaded: <?php echo h($mailLibError); ?></div>
  <?php else: ?>
  <p style="margin-top:0;">
    Sends a <strong>real test email</strong> and shows exactly what happened: the answer of the mail server, whether this server can
    reach it, and how your domain is set up for mail (SPF / DMARC). Try settings here first; when one works, copy it into
    <code>includes/mail-config.php</code>. Nothing typed here is stored.
  </p>
  <form method="post" action="#mail">
    <?php echo admin_csrf_field(); ?>
    <input type="hidden" name="action" value="mail_test">

    <div class="sc-grid">
      <div class="field">
        <label class="field-label" for="mail_to">Send the test to</label>
        <input type="email" id="mail_to" name="mail_to" required value="<?php echo h($mv('mail_to', $defaultTo)); ?>">
        <div class="sc-note">Default: the contact-form recipient.</div>
      </div>
      <div class="field">
        <label class="field-label" for="mail_transport">How to send</label>
        <?php $tsel = $mv('mail_transport', 'config'); ?>
        <select id="mail_transport" name="mail_transport">
          <option value="config" <?php echo $tsel === 'config' ? 'selected' : ''; ?>>As in mail-config.php (now: <?php echo h($mcfg['transport']); ?>)</option>
          <option value="mail" <?php echo $tsel === 'mail' ? 'selected' : ''; ?>>PHP mail()</option>
          <option value="smtp" <?php echo $tsel === 'smtp' ? 'selected' : ''; ?>>SMTP (a real mail server)</option>
        </select>
      </div>
      <div class="field">
        <label class="field-label" for="mail_from">From address</label>
        <input type="text" id="mail_from" name="mail_from" value="<?php echo h($mv('mail_from', '')); ?>" placeholder="<?php echo h($autoFrom); ?>">
        <div class="sc-note">Empty = automatic (<code><?php echo h($autoFrom); ?></code>). Should be a real mailbox of your domain.</div>
      </div>
      <div class="field">
        <label class="bd-check" style="margin-top:26px;">
          <input type="checkbox" name="mail_envelope" value="1" <?php echo ($posted ? !empty($_POST['mail_envelope']) : !empty($mcfg['envelope_sender'])) ? 'checked' : ''; ?>>
          mail(): also send the envelope sender (<code>-f</code>)
        </label>
        <div class="sc-note">Some hosts need it, others reject it — try both.</div>
      </div>
    </div>

    <h3 style="margin-bottom:6px;">SMTP settings <span class="sc-note">(used when “How to send” is SMTP, or mail-config.php says smtp)</span></h3>
    <div class="sc-grid">
      <div class="field">
        <label class="field-label" for="smtp_host">Server (host)</label>
        <input type="text" id="smtp_host" name="smtp_host" value="<?php echo h($mv('smtp_host', $mcfg['smtp']['host'])); ?>" placeholder="smtp.example.com" autocomplete="off">
      </div>
      <div class="field">
        <label class="field-label" for="smtp_port">Port</label>
        <input type="text" id="smtp_port" name="smtp_port" value="<?php echo h($mv('smtp_port', $mcfg['smtp']['port'])); ?>" autocomplete="off">
      </div>
      <div class="field">
        <label class="field-label" for="smtp_secure">Security</label>
        <?php $ssel = $mv('smtp_secure', $mcfg['smtp']['secure']); ?>
        <select id="smtp_secure" name="smtp_secure">
          <option value="tls" <?php echo $ssel === 'tls' ? 'selected' : ''; ?>>TLS / STARTTLS (port 587)</option>
          <option value="ssl" <?php echo $ssel === 'ssl' ? 'selected' : ''; ?>>SSL (port 465)</option>
          <option value="none" <?php echo $ssel === 'none' ? 'selected' : ''; ?>>None (port 25)</option>
        </select>
      </div>
      <div class="field">
        <label class="field-label" for="smtp_username">Username</label>
        <input type="text" id="smtp_username" name="smtp_username" value="<?php echo h($mv('smtp_username', $mcfg['smtp']['username'])); ?>" autocomplete="off">
      </div>
      <div class="field">
        <label class="field-label" for="smtp_password">Password</label>
        <input type="password" id="smtp_password" name="smtp_password" value="" autocomplete="new-password"
               placeholder="<?php echo !empty($mcfg['smtp']['password']) ? 'empty = use the one in mail-config.php' : ''; ?>">
        <div class="sc-note">Never stored, never shown in the results.</div>
      </div>
      <div class="field">
        <label class="bd-check" style="margin-top:26px;">
          <input type="checkbox" name="smtp_verify" value="1" <?php echo ($posted ? !empty($_POST['smtp_verify']) : !empty($mcfg['smtp']['verify_peer'])) ? 'checked' : ''; ?>>
          verify the server's certificate
        </label>
        <div class="sc-note">Untick only if the test fails with a certificate error.</div>
      </div>
    </div>

    <button type="submit" class="btn-primary">Send a test email</button>
  </form>
  <?php endif; ?>
</div>

<?php if ($mail !== null): ?>
  <?php if ($mail['error'] !== ''): ?>
    <div class="sc-find bad"><?php echo h($mail['error']); ?></div>
  <?php else: $r = $mail['result']; ?>
    <div class="sc-find <?php echo $r['ok'] ? 'ok' : 'bad'; ?>" style="font-size:1.05rem;">
      <?php if ($r['ok']): ?>
        ✅ <strong>Accepted.</strong> The message to <code><?php echo h($r['to']); ?></code> was handed over via <strong><?php echo h($r['transport']); ?></strong>.
      <?php else: ?>
        ❌ <strong>Not sent.</strong> <?php echo h($r['error']); ?>
      <?php endif; ?>
    </div>

    <?php foreach ($mail['hints'] as $hnt): ?>
      <div class="sc-find <?php echo h($hnt[0]); ?>"><?php echo h($hnt[1]); ?></div>
    <?php endforeach; ?>

    <div class="panel">
      <h2 style="margin-top:0;">What was used</h2>
      <table class="sc-kv" style="border-collapse:collapse; font-size:.9rem;">
        <tr><td>Transport</td><td><code><?php echo h($r['transport']); ?></code></td></tr>
        <tr><td>From</td><td><code><?php echo h($r['from']); ?></code></td></tr>
        <tr><td>To</td><td><code><?php echo h($r['to']); ?></code></td></tr>
        <?php if ($r['transport'] === 'smtp'): ?>
          <tr><td>SMTP server</td><td><code><?php echo h($mail['used']['smtp']['host'] . ':' . $mail['used']['smtp']['port'] . ' (' . $mail['used']['smtp']['secure'] . ')'); ?></code></td></tr>
          <tr><td>Login</td><td><code><?php echo h($mail['used']['smtp']['username'] !== '' ? $mail['used']['smtp']['username'] : '(none)'); ?></code></td></tr>
        <?php else: ?>
          <tr><td>Envelope sender (-f)</td><td><code><?php echo $mail['used']['envelope'] ? 'yes' : 'no'; ?></code></td></tr>
        <?php endif; ?>
      </table>
    </div>

    <?php if ($mail['tcp']): ?>
    <div class="panel">
      <h2 style="margin-top:0;">Outgoing connections</h2>
      <p class="sc-note" style="margin-top:0;">Can this server open a connection to the mail server? If every port fails, the host blocks outgoing mail connections.</p>
      <table class="sc-table">
        <?php foreach ($mail['tcp'] as $c): ?>
          <tr><td><code><?php echo h($c[0]); ?></code></td><?php echo sc_cell([$c[1], $c[2]]); ?><td class="sc-note"><?php echo h($c[2]); ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php endif; ?>

    <?php if ($mail['dns'] !== null): $d = $mail['dns']; ?>
    <div class="panel">
      <h2 style="margin-top:0;">How <code><?php echo h($d['domain']); ?></code> is set up for mail</h2>
      <?php if (!$d['available']): ?>
        <p class="sc-note" style="margin:0;">DNS lookups are disabled on this server, so this could not be checked.</p>
      <?php else: ?>
        <p class="sc-note" style="margin-top:0;">What receiving mail servers look at to decide between inbox, spam and rejection.</p>
        <table class="sc-table">
          <tr><td style="width:90px;"><strong>MX</strong></td><td><?php echo $d['mx'] ? h(implode(' · ', $d['mx'])) : '<span class="sc-note">none — the domain cannot receive mail</span>'; ?></td></tr>
          <tr><td><strong>SPF</strong></td><td><?php echo $d['spf'] ? '<code>' . h(implode(' | ', $d['spf'])) . '</code>' : '<span class="sc-note">none — mail will probably be marked as spam</span>'; ?></td></tr>
          <tr><td><strong>DMARC</strong></td><td><?php echo $d['dmarc'] ? '<code>' . h(implode(' | ', $d['dmarc'])) . '</code>' : '<span class="sc-note">none</span>'; ?></td></tr>
        </table>
        <p class="sc-note" style="margin-bottom:0;">DKIM cannot be checked from here (it depends on a selector only your mail provider knows).</p>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="panel">
      <h2 style="margin-top:0;">The conversation</h2>
      <p class="sc-note" style="margin-top:0;">Lines starting with <code>C:</code> are sent by this site, <code>S:</code> are the mail server's answers (a code starting with 2 or 3 is good, 4 or 5 is a refusal). Passwords are hidden.</p>
      <pre class="sc-pre"><?php echo h(implode("\n", $r['log'])); ?></pre>
    </div>

    <?php if ($r['ok']): ?>
    <div class="panel">
      <h2 style="margin-top:0;">Use these settings on the site</h2>
      <p class="sc-note" style="margin-top:0;">Create <code>includes/mail-config.php</code> with this content (replace the password). The contact form and the password reset then send the same way.</p>
      <pre class="sc-pre"><?php echo h(sc_mail_snippet($mail['used'])); ?></pre>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="panel">
    <h2 style="margin-top:0;">Report to copy</h2>
    <textarea class="sc-report" id="sc-mailreport" readonly><?php echo h($mailPlain); ?></textarea>
    <p style="margin-bottom:0;"><button type="button" class="btn-secondary" data-copy="sc-mailreport">Copy the report</button> <span class="sc-note" data-copied></span></p>
  </div>
<?php endif; ?>

<div class="panel">
  <h2 style="margin-top:0;">PHP mail setup on this server</h2>
  <table class="sc-kv" style="border-collapse:collapse; font-size:.9rem;">
    <?php foreach (sc_mail_env() as $k => $v): ?>
      <tr><td><?php echo h($k); ?></td><td><code><?php echo h($v); ?></code></td></tr>
    <?php endforeach; ?>
  </table>
</div>

<h2 id="files" style="margin-top:34px;">File &amp; SQLite check</h2>

<div class="panel">
  <form method="post">
    <?php echo admin_csrf_field(); ?>
    <p style="margin-top:0;">
      The test tries 15 different file operations in every folder, rewrites your real files with themselves,
      and runs SQLite tests (your real database and new scratch databases in each folder).
      Everything it creates is deleted again; your content is not changed.
    </p>
    <label style="display:block; margin-bottom:12px;">
      <input type="checkbox" name="commit" value="1" <?php echo $doCommit ? 'checked' : ''; ?>>
      Also commit a real write to <code>data/admin.sqlite</code> (creates and drops an empty temporary table — exactly what creating a user does)
    </label>
    <button type="submit" class="btn-primary">Run the full test</button>
  </form>
</div>

<?php if ($report !== null): ?>

<h2>What this means</h2>
<?php foreach ($report['conclusions'] as $c): ?>
  <div class="sc-find <?php echo h($c[0]); ?>"><?php echo h($c[1]); ?></div>
<?php endforeach; ?>
<?php if (!$report['conclusions']): ?>
  <div class="sc-find info">Nothing to report.</div>
<?php endif; ?>

<div class="panel">
  <h2 style="margin-top:0;">1. Folders — 15 operations in each</h2>
  <p class="sc-note" style="margin-top:0;">
    Hover a cell to read the exact message. <strong>create</strong> = open+write a new file (how the CMS saves) ·
    <strong>put</strong> = file_put_contents · <strong>append</strong> · <strong>read</strong> back ·
    <strong>rename</strong> · <strong>copy</strong> · <strong>touch</strong> · <strong>chmod</strong> (failing is normal) ·
    <strong>delete</strong> · <strong>mkdir</strong> · <strong>in-dir</strong> = file inside the new folder · <strong>rmdir</strong> ·
    <strong>.php / .txt / no-ext</strong> = does the file name matter?
  </p>
  <div class="sc-scroll">
  <table class="sc-table">
    <thead>
      <tr>
        <th>Folder</th><th>Rights / owner</th>
        <?php foreach (['create','put','append','read','rename','copy','touch','chmod','delete','mkdir','in-dir','rmdir','.php','.txt','no-ext'] as $n): ?>
          <th style="text-align:center;"><?php echo h($n); ?></th>
        <?php endforeach; ?>
        <th>First error</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($report['folders'] as $f): ?>
      <tr>
        <td>
          <strong><?php echo h($f['label']); ?></strong><br>
          <span class="sc-note"><?php echo h($f['used']); ?></span>
        </td>
        <?php if (!$f['exists']): ?>
          <td colspan="17" class="sc-note">does not exist (<?php echo h($f['path'] !== '' ? $f['path'] : 'no path'); ?>)</td>
        <?php else: ?>
          <td style="white-space:nowrap;"><code><?php echo h($f['perm']); ?></code></td>
          <?php
            $firstErr = '—';
            foreach ($f['ops'] as $name => $r) {
                if ($name !== 'chmod' && $r[0] === false) { $firstErr = $name . ': ' . $r[1]; break; }
            }
            foreach (['create','put','append','read','rename','copy','touch','chmod','delete','mkdir','in-dir','rmdir','.php','.txt','no-ext'] as $n) {
                echo sc_cell($f['ops'][$n] ?? [null, 'not run']);
            }
          ?>
          <td class="sc-note"><?php echo h($firstErr); ?></td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="panel">
  <h2 style="margin-top:0;">2. Existing files — can they be written?</h2>
  <p class="sc-note" style="margin-top:0;">
    "Rewrite" opens the file for writing and writes its first byte back unchanged. "PHP says" is what PHP's own
    is_writable() claims — it can be wrong, which is why the real rewrite is tested.
  </p>
  <div class="sc-scroll">
  <table class="sc-table">
    <thead><tr><th>File</th><th>Rights / owner</th><th>Size</th><th>PHP says writable</th><th style="text-align:center;">Read</th><th style="text-align:center;">Rewrite</th><th>Message</th></tr></thead>
    <tbody>
    <?php foreach ($report['files'] as $fl): ?>
      <tr>
        <td><code><?php echo h(str_replace($root . '/', '', $fl['path'])); ?></code></td>
        <?php if (!$fl['exists']): ?>
          <td colspan="6" class="sc-note">does not exist</td>
        <?php else: ?>
          <td style="white-space:nowrap;"><code><?php echo h($fl['perm']); ?></code></td>
          <td><?php echo h(sc_bytes($fl['size'])); ?></td>
          <td><?php echo $fl['is_writable'] ? 'yes' : 'no'; ?></td>
          <?php echo sc_cell($fl['read']); echo sc_cell($fl['rewrite']); ?>
          <td class="sc-note"><?php echo h($fl['rewrite'][0] === false ? $fl['rewrite'][1] : ($fl['read'][0] === false ? $fl['read'][1] : '')); ?></td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="panel">
  <h2 style="margin-top:0;">3. SQLite</h2>
  <?php $sq = $report['sqlite']; ?>
  <?php if (!$sq['available']): ?>
    <div class="sc-find bad">The PDO SQLite driver is not available on this server.</div>
  <?php else: ?>
    <p class="sc-note" style="margin-top:0;">
      SQLite library version <strong><?php echo h($sq['version']); ?></strong> · in-memory database:
      <?php echo $sq['memory'] ? ($sq['memory'][0] ? '✅ works' : '❌ ' . h($sq['memory'][1])) : '—'; ?>
    </p>

    <h3>Your real database — <code>data/admin.sqlite</code></h3>
    <table class="sc-table" style="margin-bottom:18px;">
      <tbody>
      <?php foreach ($sq['real'] as $s): ?>
        <tr>
          <td><?php echo h($s[0]); ?></td>
          <?php echo sc_cell([$s[1], $s[2]]); ?>
          <td class="sc-note">
            <?php echo h($s[2]); ?>
            <?php if ($s[1] === false && sc_sqlite_hint($s[2]) !== ''): ?><br><em><?php echo h(sc_sqlite_hint($s[2])); ?></em><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <h3>Brand-new scratch databases, one per folder</h3>
    <p class="sc-note" style="margin-top:0;">
      Each database is created, filled, read and deleted. Where that works it is repeated with other journal modes
      (WAL / MEMORY / TRUNCATE), because some hosts only allow certain modes.
    </p>
    <div class="sc-scroll">
    <table class="sc-table">
      <thead>
        <tr><th>Folder</th><th style="text-align:center;">open</th><th style="text-align:center;">create table</th><th style="text-align:center;">insert</th><th style="text-align:center;">select</th><th style="text-align:center;">delete row</th>
            <th style="text-align:center;">WAL</th><th style="text-align:center;">MEMORY</th><th style="text-align:center;">TRUNCATE</th><th>First error</th></tr>
      </thead>
      <tbody>
      <?php foreach ($sq['scratch'] as $e): ?>
        <tr>
          <td><strong><?php echo h($e['label']); ?></strong></td>
          <?php
            $first = '—';
            foreach (['open','create table','insert','select','delete row'] as $n) {
                $r = $e['steps'][$n] ?? [null, 'not run'];
                echo sc_cell($r);
                if ($first === '—' && $r[0] === false) $first = $n . ': ' . $r[1];
            }
            foreach (['WAL','MEMORY','TRUNCATE'] as $mode) {
                if (!isset($e['modes'][$mode])) { echo sc_cell([null, 'not tried (the basic test failed)']); continue; }
                $res = [true, 'ok'];
                foreach ($e['modes'][$mode] as $s) {
                    if ($s[0] === false) { $res = [false, $s[1]]; break; }
                }
                echo sc_cell($res);
                if ($first === '—' && $res[0] === false) $first = $mode . ': ' . $res[1];
            }
            $hint = ($first !== '—') ? sc_sqlite_hint($first) : '';
          ?>
          <td class="sc-note"><?php echo h($first); ?><?php if ($hint !== ''): ?><br><em><?php echo h($hint); ?></em><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</div>

<div class="panel">
  <h2 style="margin-top:0;">Report to copy</h2>
  <p class="sc-note" style="margin-top:0;">Plain text of everything above. Paste it to me, or to your host's support.</p>
  <textarea class="sc-report" id="sc-report" readonly><?php echo h($plain); ?></textarea>
  <p style="margin-bottom:0;"><button type="button" class="btn-secondary" id="sc-copy">Copy the report</button> <span id="sc-copied" class="sc-note"></span></p>
</div>
<script>
  document.getElementById('sc-copy').addEventListener('click', function () {
    var t = document.getElementById('sc-report');
    t.select();
    var done = function () { document.getElementById('sc-copied').textContent = 'Copied.'; };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(t.value).then(done, function () { document.execCommand('copy'); done(); });
    } else { document.execCommand('copy'); done(); }
  });
</script>

<?php endif; ?>

<div class="panel">
  <h2 style="margin-top:0;">Server facts</h2>
  <table class="sc-kv" style="border-collapse:collapse; font-size:.9rem;">
    <?php foreach ($env as $k => $v): ?>
      <tr><td><?php echo h($k); ?></td><td><code><?php echo h($v); ?></code></td></tr>
    <?php endforeach; ?>
  </table>
</div>

<script>
  // Generic "copy" buttons: <button data-copy="id-of-textarea">
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var t = document.getElementById(btn.getAttribute('data-copy'));
      if (!t) return;
      t.select();
      var note = btn.parentNode.querySelector('[data-copied]');
      var done = function () { if (note) note.textContent = 'Copied.'; };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(t.value).then(done, function () { document.execCommand('copy'); done(); });
      } else { document.execCommand('copy'); done(); }
    });
  });
</script>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
