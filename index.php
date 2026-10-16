<?php
$isRootEntry = !isset($lang); // true only when visited directly as "/" or "/index.php"
if (!isset($lang)) { $lang = 'en'; }
require_once __DIR__ . '/includes/config.php';

if ($isRootEntry) {
    require_once __DIR__ . '/includes/language-detect.php'; // may redirect + exit
}

$pageTitle = t('home.meta.title');
$pageDescription = t('home.meta.description');
$pageKeywords = t('home.meta.keywords');
require_once __DIR__ . '/includes/header.php';

// The page body is built from blocks (edited in /admin/builder.php).
enw_render_blocks('home');
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
