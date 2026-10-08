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

<div class="dashboard-grid">
  <?php foreach ($schema as $pageKey => $pageDef): ?>
  <div class="dashboard-card">
    <h2><?php echo htmlspecialchars($pageDef['label']); ?></h2>
    <div class="lang-links">
      <?php foreach ($languages as $code => $name): ?>
        <a href="edit.php?page=<?php echo urlencode($pageKey); ?>&lang=<?php echo urlencode($code); ?>"><?php echo htmlspecialchars($name); ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
