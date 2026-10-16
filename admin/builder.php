<?php
// The block builder: blocks + their content on the left, a live preview of
// the real page on the right.
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/includes/blocks-store.php';

$pages = enw_block_pages();
$languages = ['en' => 'English', 'fr' => 'Français', 'zh' => '中文', 'ja' => '日本語'];

$page = $_GET['page'] ?? '';
$lang = $_GET['lang'] ?? '';
if (!isset($pages[$page]) || !cs_valid_lang($lang)) {
    header('Location: index.php');
    exit;
}

// First run: the site has not been converted to blocks yet.
if (!enw_structure_exists()) {
    header('Location: migrate-blocks.php');
    exit;
}

$state = bs_editor_state($page, $lang);

$types = [];
foreach (enw_block_types() as $key => $def) {
    $types[$key] = $def;
}

$boot = [
    'page'       => $page,
    'pageLabel'  => $pages[$page],
    'lang'       => $lang,
    'csrf'       => admin_csrf_token(),
    'types'      => $types,
    'links'      => enw_link_options(),
    'library'    => bs_image_library(),
    'imgBase'    => '/assets/img/',
    'saveUrl'    => 'builder-save.php',
    'uploadUrl'  => 'builder-upload.php',
    'previewUrl' => 'preview.php?page=' . rawurlencode($page) . '&lang=' . rawurlencode($lang),
    'state'      => $state,
    'isDefault'  => enw_page_is_default($page),   // built-in content that has never been saved (404 page)
];
$bootJson = json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?php echo htmlspecialchars($pages[$page] . ' — ' . $languages[$lang]); ?> · Builder · Enwires Admin</title>
<link rel="stylesheet" href="assets/admin.css">
<link rel="stylesheet" href="assets/builder.css">
</head>
<body class="builder">

<header class="bd-top">
  <a href="index.php" class="bd-back" title="Back to dashboard">&larr;</a>
  <div class="bd-title">
    <strong><?php echo htmlspecialchars($pages[$page]); ?></strong>
    <nav class="bd-langs" aria-label="Language">
      <?php foreach ($languages as $code => $name): ?>
        <a href="builder.php?page=<?php echo urlencode($page); ?>&amp;lang=<?php echo urlencode($code); ?>"
           data-lang-link class="<?php echo $code === $lang ? 'is-active' : ''; ?>"><?php echo htmlspecialchars($name); ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
  <div class="bd-switch" role="group" aria-label="Preview width">
    <button type="button" data-device="desktop" class="is-active" title="Desktop">Desktop</button>
    <button type="button" data-device="tablet" title="Tablet">Tablet</button>
    <button type="button" data-device="mobile" title="Mobile">Mobile</button>
  </div>
  <span id="bd-status" class="bd-status" aria-live="polite">All changes saved</span>
  <button type="button" id="bd-save" class="btn-primary bd-save">Save</button>
</header>

<div class="bd-main">
  <aside class="bd-side" id="bd-side">
    <div class="bd-notice" id="bd-notice"></div>
    <div id="bd-meta"></div>
    <div id="bd-blocks"></div>
  </aside>

  <section class="bd-preview" id="bd-preview">
    <div class="bd-frame-wrap" id="bd-frame-wrap">
      <iframe id="bd-frame" title="Page preview" src="<?php echo htmlspecialchars($boot['previewUrl']); ?>"></iframe>
    </div>
    <div class="bd-preview-note" id="bd-preview-note"></div>
  </section>
</div>

<!-- Block picker -->
<div class="bd-modal" id="bd-picker" hidden>
  <div class="bd-modal-box" role="dialog" aria-modal="true" aria-labelledby="bd-picker-title">
    <div class="bd-modal-head">
      <h2 id="bd-picker-title">Add a block</h2>
      <button type="button" class="btn-icon" id="bd-picker-close" aria-label="Close">&times;</button>
    </div>
    <div class="bd-picker-grid" id="bd-picker-grid"></div>
  </div>
</div>

<script type="application/json" id="enw-boot"><?php echo $bootJson; ?></script>
<script src="assets/builder.js"></script>
</body>
</html>
