<?php
// System check: can the web server write where the CMS needs to save?
//
// The main test creates content/test.json, writes to it, reads it back and
// deletes it — the same way the CMS saves its content. If something fails,
// the page shows the exact commands to run over SSH.

require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/includes/blocks-store.php';

$root       = dirname(__DIR__);
$contentDir = $root . '/content';
$dataDir    = $root . '/data';
$backupsDir = $dataDir . '/backups';
$imgDir     = $root . '/assets/img';

// --- helpers --------------------------------------------------------------

/** Owner and permissions of a path, e.g. "0755 www-data". */
function sc_perm($path) {
    if (!file_exists($path)) {
        return 'missing';
    }
    $perm = substr(sprintf('%o', @fileperms($path)), -4);
    $owner = '?';
    $uid = @fileowner($path);
    if ($uid !== false) {
        $owner = (string) $uid;
        if (function_exists('posix_getpwuid')) {
            $u = @posix_getpwuid($uid);
            if (is_array($u) && isset($u['name'])) $owner = $u['name'];
        }
    }
    return $perm . ' ' . $owner;
}

/** The system user PHP is running as. */
function sc_php_user() {
    if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
        $u = @posix_getpwuid(posix_geteuid());
        if (is_array($u) && isset($u['name'])) return $u['name'];
    }
    $u = get_current_user();
    return $u !== '' ? $u : 'www-data';
}

/**
 * Create / write / read back / delete a JSON file, using the same locked
 * write the CMS uses when it saves. Returns [bool ok, string message].
 */
function sc_write_test($path) {
    $payload = ['test' => 'ok', 'time' => date('c'), 'rand' => bin2hex(random_bytes(6))];

    $fp = @fopen($path, 'c+');
    if (!$fp) {
        $e = error_get_last();
        return [false, 'Could not create or open the file. ' . ($e['message'] ?? '')];
    }
    $wrote = false;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        rewind($fp);
        $wrote = fwrite($fp, json_encode($payload) . "\n") !== false;
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    if (!$wrote) {
        @unlink($path);
        return [false, 'The file opened but writing to it failed.'];
    }

    $back = json_decode((string) @file_get_contents($path), true);
    if (!is_array($back) || ($back['rand'] ?? null) !== $payload['rand']) {
        @unlink($path);
        return [false, 'Written, but reading it back gave different content.'];
    }

    if (!@unlink($path)) {
        return [true, 'Created, written and read back. (The test file could not be deleted — delete ' . basename($path) . ' by hand.)'];
    }
    return [true, 'Created, written, read back and deleted.'];
}

/** Can an EXISTING file be opened for writing? (Does not change it.) */
function sc_open_test($path) {
    if (!file_exists($path)) {
        return [null, 'Not created yet (fine — it is created on first save).'];
    }
    $fp = @fopen($path, 'c+');
    if (!$fp) {
        $e = error_get_last();
        return [false, 'Not writable. ' . ($e['message'] ?? '')];
    }
    fclose($fp);
    return [true, 'Writable.'];
}

// --- run the tests (only when the button is pressed) ------------------------

$results = null;
$allOk = null;
$failedPaths = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf()) {
        $results = [];
        $allOk = false;
        $csrfFailed = true;
    } else {
        $results = [];

        // 1) The main test: create/write/read/delete content/test.json
        [$ok, $msg] = sc_write_test($contentDir . '/test.json');
        $results[] = ['title' => 'Write content/test.json', 'path' => $contentDir, 'ok' => $ok, 'msg' => $msg, 'main' => true];

        // 2) The real content files
        foreach (['en', 'fr', 'zh', 'ja'] as $l) {
            [$ok, $msg] = sc_open_test($contentDir . "/$l.json");
            $results[] = ['title' => "content/$l.json", 'path' => $contentDir . "/$l.json", 'ok' => $ok, 'msg' => $msg];
        }
        foreach (['structure.json', 'site.json'] as $f) {
            [$ok, $msg] = sc_open_test($contentDir . '/' . $f);
            $results[] = ['title' => "content/$f", 'path' => $contentDir . '/' . $f, 'ok' => $ok, 'msg' => $msg];
        }

        // 3) Backups folder (created on first save if missing)
        if (!is_dir($backupsDir)) {
            @mkdir($backupsDir, 0775, true);
        }
        if (is_dir($backupsDir)) {
            [$ok, $msg] = sc_write_test($backupsDir . '/test-' . bin2hex(random_bytes(3)) . '.json');
        } else {
            [$ok, $msg] = [false, 'The folder does not exist and could not be created.'];
        }
        $results[] = ['title' => 'Write data/backups/ (backups before each save)', 'path' => $backupsDir, 'ok' => $ok, 'msg' => $msg];

        // 4) SQLite database (users, password resets): the file AND its folder must be writable
        [$ok, $msg] = sc_open_test($dataDir . '/admin.sqlite');
        $results[] = ['title' => 'data/admin.sqlite (admin users)', 'path' => $dataDir . '/admin.sqlite', 'ok' => $ok, 'msg' => $msg];
        [$ok, $msg] = sc_write_test($dataDir . '/test-' . bin2hex(random_bytes(3)) . '.json');
        $results[] = ['title' => 'Write data/ folder (SQLite needs this too)', 'path' => $dataDir, 'ok' => $ok, 'msg' => $msg];

        // 5) Image uploads
        if (is_dir($imgDir)) {
            [$ok, $msg] = sc_write_test($imgDir . '/test-' . bin2hex(random_bytes(3)) . '.json');
        } else {
            [$ok, $msg] = [false, 'assets/img does not exist.'];
        }
        $results[] = ['title' => 'Write assets/img/ (image uploads)', 'path' => $imgDir, 'ok' => $ok, 'msg' => $msg];

        $allOk = true;
        foreach ($results as $r) {
            if ($r['ok'] === false) {
                $allOk = false;
                $failedPaths[] = $r['path'];
            }
        }
    }
}

$phpUser = sc_php_user();

// Read-only info shown on every visit.
$ini = [
    'PHP version'          => PHP_VERSION,
    'PHP runs as user'     => $phpUser,
    'upload_max_filesize'  => ini_get('upload_max_filesize'),
    'post_max_size'        => ini_get('post_max_size'),
];
$exts = [];
foreach (['gd' => 'images', 'fileinfo' => 'upload checks', 'mbstring' => 'text', 'pdo_sqlite' => 'admin users', 'json' => 'content'] as $ext => $why) {
    $exts[$ext] = [extension_loaded($ext), $why];
}

$pageTitle = 'System check';
require __DIR__ . '/includes/layout-header.php';
?>

<a href="index.php" class="back-link">&larr; Back to dashboard</a>
<h1>System check</h1>
<p class="subtitle">Checks that the server can save your content, backups, users and images.</p>

<?php if (!empty($csrfFailed)): ?>
  <div class="alert alert-error">Your session expired — please try again.</div>
<?php elseif ($allOk === true): ?>
  <div class="alert alert-success">All write tests passed. Saving from the admin will work.</div>
<?php elseif ($allOk === false): ?>
  <div class="alert alert-error">Some write tests failed. Saving from the admin would fail until this is fixed — see the commands below.</div>
<?php endif; ?>

<div class="panel">
  <form method="post">
    <?php echo admin_csrf_field(); ?>
    <p style="margin-top:0;">
      This creates <code>content/test.json</code>, writes to it, reads it back and deletes it — exactly how the CMS saves —
      then does the same for the other places the CMS writes. Nothing of your content is changed.
    </p>
    <button type="submit" class="btn-primary">Run the write test</button>
  </form>
</div>

<?php if ($results): ?>
<div class="panel">
  <h2 style="margin-top:0;">Results</h2>
  <table style="width:100%; border-collapse:collapse; font-size:.92rem;">
    <thead>
      <tr style="text-align:left; border-bottom:1px solid var(--line);">
        <th style="padding:8px 6px;">Test</th><th style="padding:8px 6px;">Result</th>
        <th style="padding:8px 6px;">Rights / owner</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($results as $r): ?>
      <tr style="border-bottom:1px solid var(--line); vertical-align:top;">
        <td style="padding:8px 6px;">
          <?php echo $r['ok'] === true ? '✅' : ($r['ok'] === false ? '❌' : '➖'); ?>
          <?php echo !empty($r['main']) ? '<strong>' : ''; ?><?php echo htmlspecialchars($r['title']); ?><?php echo !empty($r['main']) ? '</strong>' : ''; ?>
        </td>
        <td style="padding:8px 6px;"><?php echo htmlspecialchars($r['msg']); ?></td>
        <td style="padding:8px 6px; white-space:nowrap;"><code><?php echo htmlspecialchars(sc_perm($r['path'])); ?></code></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if ($allOk === false && empty($csrfFailed)): ?>
<div class="panel">
  <h2 style="margin-top:0;">How to fix it over SSH</h2>
  <p>PHP runs as the user <strong><?php echo htmlspecialchars($phpUser); ?></strong>, and that user must be allowed to write. Connect to the server, then run:</p>
<pre style="background:#f3f3f5; padding:12px; border-radius:6px; overflow:auto;"><?php
echo htmlspecialchars(
"cd " . $root . "\n\n" .
"# 1. give the web user ownership of the folders the CMS writes to\n" .
"sudo chown -R " . $phpUser . ":" . $phpUser . " content data assets/img\n\n" .
"# 2. folders: read/write/enter, files: read/write\n" .
"sudo find content data assets/img -type d -exec chmod 775 {} \\;\n" .
"sudo find content data assets/img -type f -exec chmod 664 {} \\;\n\n" .
"# 3. make sure the backups folder exists\n" .
"sudo -u " . $phpUser . " mkdir -p data/backups"
);
?></pre>
  <p>Then come back here and run the test again. Notes:</p>
  <ul>
    <li>Without <code>sudo</code> (shared hosting), drop the word <code>sudo</code> and the <code>chown</code> line — your SSH account usually already owns the files, and only the <code>chmod</code> lines are needed. If it still fails, use <code>775</code> → <code>777</code> on the failing folders only as a last resort and ask your host which user PHP runs as.</li>
    <li>A ❌ on a <strong>folder</strong> means the folder itself must be writable (creating <code>test.json</code> and backups needs that). A ❌ on a single <strong>file</strong> means that file's rights or owner.</li>
    <li>The SQLite database needs both the file <em>and</em> the <code>data/</code> folder to be writable.</li>
  </ul>
</div>
<?php endif; ?>

<div class="panel">
  <h2 style="margin-top:0;">Server info</h2>
  <table style="border-collapse:collapse; font-size:.92rem;">
    <?php foreach ($ini as $k => $v): ?>
      <tr><td style="padding:4px 18px 4px 0; color:var(--ink-dim);"><?php echo htmlspecialchars($k); ?></td><td><code><?php echo htmlspecialchars((string) $v); ?></code></td></tr>
    <?php endforeach; ?>
    <?php foreach ($exts as $ext => $info): ?>
      <tr>
        <td style="padding:4px 18px 4px 0; color:var(--ink-dim);">PHP extension <code><?php echo htmlspecialchars($ext); ?></code> (<?php echo htmlspecialchars($info[1]); ?>)</td>
        <td><?php echo $info[0] ? '✅ loaded' : '❌ missing — ask your host to enable it'; ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
