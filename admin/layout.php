<?php
// Header & footer editor: settings on the left, live preview of a real page on the right.
// What is saved here is used by EVERY page (see includes/header.php, footer.php).
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/includes/layout-store.php';

$languages = ['en' => 'English', 'fr' => 'Français', 'zh' => '中文', 'ja' => '日本語'];
$lang = $_GET['lang'] ?? 'en';
if (!cs_valid_lang($lang)) {
    $lang = 'en';
}

$previewPages = [];
foreach (enw_site_pages() as $key => $p) {
    $previewPages[$key] = $p['label'];
}

$boot = [
    'lang'         => $lang,
    'csrf'         => admin_csrf_token(),
    'state'        => ls_state($lang),
    'pages'        => enw_nav_page_options(),
    'library'      => bs_image_library(true),
    'imgBase'      => '/assets/img/',
    'saveUrl'      => 'layout-save.php',
    'uploadUrl'    => 'builder-upload.php',
    'previewBase'  => 'preview.php?lang=' . rawurlencode($lang) . '&page=',
    'maxItems'     => ENW_MAX_NAV_ITEMS,
    'defaultCopy'  => ENW_DEFAULT_COPYRIGHT,
];
$bootJson = json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Header &amp; footer · Enwires Admin</title>
<link rel="stylesheet" href="assets/admin.css">
<link rel="stylesheet" href="assets/builder.css">
</head>
<body class="builder">

<header class="bd-top">
  <a href="index.php" class="bd-back" title="Back to dashboard">&larr;</a>
  <div class="bd-title">
    <strong>Header &amp; footer</strong>
    <nav class="bd-langs" aria-label="Language">
      <?php foreach ($languages as $code => $name): ?>
        <a href="layout.php?lang=<?php echo urlencode($code); ?>" data-lang-link
           class="<?php echo $code === $lang ? 'is-active' : ''; ?>"><?php echo htmlspecialchars($name); ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
  <label class="bd-previewpick">Preview on
    <select id="lp-page">
      <?php foreach ($previewPages as $key => $label): ?>
        <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="bd-switch" role="group" aria-label="Preview width">
    <button type="button" data-device="desktop" class="is-active">Desktop</button>
    <button type="button" data-device="tablet">Tablet</button>
    <button type="button" data-device="mobile">Mobile</button>
  </div>
  <span id="bd-status" class="bd-status" aria-live="polite">All changes saved</span>
  <button type="button" id="bd-save" class="btn-primary bd-save">Save</button>
</header>

<div class="bd-main">
  <aside class="bd-side" id="bd-side">
    <div class="bd-notice" id="bd-notice"></div>
    <div id="lp-form-area"></div>
  </aside>

  <section class="bd-preview">
    <div class="bd-frame-wrap" id="bd-frame-wrap">
      <iframe id="bd-frame" name="bd-frame" title="Page preview" src="preview.php?lang=<?php echo urlencode($lang); ?>&amp;page=home"></iframe>
    </div>
    <div class="bd-preview-note" id="bd-preview-note"></div>
  </section>
</div>

<!-- Posts the unsaved draft into the preview frame -->
<form id="lp-post" method="post" action="" target="bd-frame" hidden>
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($boot['csrf']); ?>">
  <input type="hidden" name="layout_json" id="lp-json" value="">
</form>

<script type="application/json" id="enw-boot"><?php echo $bootJson; ?></script>
<script src="assets/layout.js"></script>
</body>
</html>
