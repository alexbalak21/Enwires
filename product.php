<?php
if (!isset($lang)) { $lang = 'en'; }
require_once __DIR__ . '/includes/config.php';

$pageTitle = t('product.meta.title');
$pageDescription = t('product.meta.description');
$pageKeywords = t('product.meta.keywords');
// Social-share image: the photo of the first "Photo with caption" block, if any.
$ogSrc = enw_first_image_src('product', 'figure');
if ($ogSrc) { $pageImage = 'assets/img/' . $ogSrc; }
require_once __DIR__ . '/includes/header.php';

// The page body is built from blocks (edited in /admin/builder.php).
enw_render_blocks('product');
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
