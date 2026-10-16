<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/includes/field-schema.php';

$schema = require __DIR__ . '/includes/field-schema.php';
$languages = [
    'en' => 'English',
    'fr' => 'Français',
    'zh' => '中文',
    'ja' => '日本語',
];

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/layout-header.php';
?>

<h1>Content</h1>
<p class="subtitle">Pick a page and language to edit. Manage admin accounts under <a href="users.php">Users</a>.</p>

<?php
require_once dirname(__DIR__) . '/includes/blocks-core.php';
$blockPages = enw_block_pages();   // pages edited with the block builder
$converted = enw_structure_exists();
?>
<?php if (!$converted): ?>
  <div class="alert alert-error">
    Home and Product are not converted to blocks yet.
    <a href="migrate-blocks.php">Convert them now</a> (one click, files are backed up first).
  </div>
<?php endif; ?>

<div class="dashboard-grid">
  <?php foreach ($schema as $pageKey => $pageDef): ?>
  <?php $isBlockPage = isset($blockPages[$pageKey]); ?>
  <div class="dashboard-card">
    <h2><?php echo htmlspecialchars($pageDef['label']); ?><?php if ($isBlockPage): ?> <span class="you-tag">block builder</span><?php endif; ?></h2>
    <div class="lang-links">
      <?php foreach ($languages as $code => $name): ?>
        <?php if ($isBlockPage): ?>
          <a href="<?php echo $converted ? 'builder.php' : 'migrate-blocks.php'; ?>?page=<?php echo urlencode($pageKey); ?>&lang=<?php echo urlencode($code); ?>"><?php echo htmlspecialchars($name); ?></a>
        <?php else: ?>
          <a href="edit.php?page=<?php echo urlencode($pageKey); ?>&lang=<?php echo urlencode($code); ?>"><?php echo htmlspecialchars($name); ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="dashboard-card">
    <h2>Header &amp; footer <span class="you-tag">all pages</span></h2>
    <div class="lang-links">
      <?php foreach ($languages as $code => $name): ?>
        <a href="layout.php?lang=<?php echo urlencode($code); ?>"><?php echo htmlspecialchars($name); ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="dashboard-card">
    <h2>404 page <span class="you-tag">block builder</span></h2>
    <div class="lang-links">
      <?php foreach ($languages as $code => $name): ?>
        <a href="<?php echo $converted ? 'builder.php' : 'migrate-blocks.php'; ?>?page=notfound&amp;lang=<?php echo urlencode($code); ?>"><?php echo htmlspecialchars($name); ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="dashboard-card">
    <h2>System check</h2>
    <div class="lang-links">
      <a href="system-check.php">Test file writing</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
