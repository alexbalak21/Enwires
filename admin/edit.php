<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/includes/content-store.php';
require_once __DIR__ . '/includes/uploads.php';
require_once __DIR__ . '/includes/render-fields.php';

$schema = require __DIR__ . '/includes/field-schema.php';
$languages = ['en' => 'English', 'fr' => 'Français', 'zh' => '中文', 'ja' => '日本語'];
$assetsBaseUrl = '/';

$page = $_GET['page'] ?? ($_POST['page'] ?? '');
$lang = $_GET['lang'] ?? ($_POST['lang'] ?? '');

if (!isset($schema[$page]) || !cs_valid_lang($lang)) {
    header('Location: index.php');
    exit;
}
$pageDef = $schema[$page];

/** Find a field's definition by its path, searching every section. */
function find_field_by_path($pageDef, $path) {
    foreach ($pageDef['sections'] as $section) {
        foreach ($section['fields'] as $field) {
            if ($field['path'] === $path) {
                return $field;
            }
        }
    }
    return null;
}

$messages = [];
$justSaved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf()) {
        $messages[] = ['type' => 'error', 'text' => 'Your session expired — your edits below were not applied. Please make your changes again.'];
        $fullData = cs_load($lang);
    } else {
        $fullData = cs_load($lang);
        $posted = $_POST['field'] ?? [];

        // Overlay every field belonging to this page from the posted data
        // onto the full (all-pages) content array for this language.
        foreach ($pageDef['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                $path = $field['path'];

                if ($field['type'] === 'text' || $field['type'] === 'textarea') {
                    $value = cs_get($posted, $path);
                    cs_set($fullData, $path, (string)($value ?? ''));

                } elseif ($field['type'] === 'repeater') {
                    $rows = cs_get($posted, $path, []);
                    $rows = is_array($rows) ? array_values($rows) : [];
                    $clean = [];
                    foreach ($rows as $row) {
                        $cleanRow = [];
                        foreach ($field['item_fields'] as $sub) {
                            $cleanRow[$sub['name']] = (string)($row[$sub['name']] ?? '');
                        }
                        $clean[] = $cleanRow;
                    }
                    cs_set($fullData, $path, $clean);

                } elseif ($field['type'] === 'gallery') {
                    $rows = cs_get($posted, $path, []);
                    $rows = is_array($rows) ? array_values($rows) : [];
                    $clean = [];
                    foreach ($rows as $idx => $row) {
                        $src = (string)($row['src'] ?? '');
                        $alt = (string)($row['alt'] ?? '');
                        $fileInfo = get_uploaded_gallery_file($path, $idx);
                        $result = handle_image_upload($fileInfo);
                        if (is_upload_error($result)) {
                            $messages[] = ['type' => 'error', 'text' => $field['label'] . ' — image ' . ($idx + 1) . ': ' . $result['error']];
                        } elseif ($result !== null) {
                            $src = $result;
                        }
                        $clean[] = ['src' => $src, 'alt' => $alt];
                    }
                    cs_set($fullData, $path, $clean);

                } elseif ($field['type'] === 'image') {
                    $row = cs_get($posted, $path, []);
                    $row = is_array($row) ? $row : [];
                    $src = (string)($row['src'] ?? '');
                    $alt = (string)($row['alt'] ?? '');
                    $fileInfo = get_uploaded_single_file($path);
                    $result = handle_image_upload($fileInfo);
                    if (is_upload_error($result)) {
                        $messages[] = ['type' => 'error', 'text' => $field['label'] . ': ' . $result['error']];
                    } elseif ($result !== null) {
                        $src = $result;
                    }
                    $value = ['src' => $src, 'alt' => $alt];
                    $existing = cs_get($fullData, $path, []);
                    if (is_array($existing) && array_key_exists('caption', $existing)) {
                        $value['caption'] = (string)($row['caption'] ?? '');
                    }
                    cs_set($fullData, $path, $value);
                }
            }
        }

        // Apply whichever button was clicked.
        $actionRaw = $_POST['action'] ?? 'save';
        $parts = explode(':', $actionRaw, 3);
        $actionType = $parts[0];

        if ($actionType === 'save') {
            if (cs_save($lang, $fullData)) {
                header('Location: edit.php?page=' . urlencode($page) . '&lang=' . urlencode($lang) . '&saved=1');
                exit;
            }
            $messages[] = ['type' => 'error', 'text' => 'Could not save — please try again.'];
        } elseif ($actionType === 'add' && isset($parts[1])) {
            $targetField = find_field_by_path($pageDef, $parts[1]);
            if ($targetField) {
                if ($targetField['type'] === 'repeater') {
                    $blank = [];
                    foreach ($targetField['item_fields'] as $sub) { $blank[$sub['name']] = ''; }
                    cs_array_add($fullData, $parts[1], $blank);
                } elseif ($targetField['type'] === 'gallery') {
                    cs_array_add($fullData, $parts[1], ['src' => '', 'alt' => '']);
                }
            }
        } elseif (in_array($actionType, ['remove', 'move_up', 'move_down'], true) && isset($parts[1], $parts[2])) {
            $targetPath = $parts[1];
            $index = (int)$parts[2];
            if ($actionType === 'remove') {
                cs_array_remove_at($fullData, $targetPath, $index);
            } else {
                cs_array_move($fullData, $targetPath, $index, $actionType === 'move_up' ? 'up' : 'down');
            }
        }
        // Falls through to render below, using the in-memory $fullData —
        // nothing is written to disk unless action was "save".
    }
} else {
    $fullData = cs_load($lang);
    if (isset($_GET['saved'])) {
        $justSaved = true;
    }
}

$pageTitle = $pageDef['label'] . ' — ' . $languages[$lang];
require __DIR__ . '/includes/layout-header.php';
?>

<a href="index.php" class="back-link">&larr; Back to dashboard</a>
<h1><?php echo htmlspecialchars($pageDef['label']); ?></h1>
<p class="subtitle">Editing the <?php echo htmlspecialchars($languages[$lang]); ?> version.</p>

<div class="lang-tabs">
  <?php foreach ($languages as $code => $name): ?>
    <a href="edit.php?page=<?php echo urlencode($page); ?>&lang=<?php echo urlencode($code); ?>" class="<?php echo $code === $lang ? 'is-active' : ''; ?>"><?php echo htmlspecialchars($name); ?></a>
  <?php endforeach; ?>
</div>

<?php if ($justSaved): ?>
  <div class="alert alert-success">Saved.</div>
<?php endif; ?>
<?php foreach ($messages as $m): ?>
  <div class="alert alert-<?php echo $m['type']; ?>"><?php echo htmlspecialchars($m['text']); ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data">
  <?php echo admin_csrf_field(); ?>
  <input type="hidden" name="page" value="<?php echo htmlspecialchars($page); ?>">
  <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
  <!-- Pressing Enter in any text field defaults to Save, not the first visible button. -->
  <button type="submit" name="action" value="save" style="position:absolute; width:1px; height:1px; overflow:hidden; opacity:0;" aria-hidden="true" tabindex="-1"></button>

  <?php foreach ($pageDef['sections'] as $section): ?>
  <div class="panel section-block">
    <h2><?php echo htmlspecialchars($section['label']); ?></h2>
    <br>
    <?php foreach ($section['fields'] as $field): ?>
      <?php render_field($field, $fullData, $assetsBaseUrl); ?>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>

  <div class="form-actions">
    <button type="submit" name="action" value="save" class="btn-primary">Save changes</button>
  </div>
</form>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
