<?php
// The "page not found" page. Shown by router.php for any unknown URL.
// Its content is made of blocks, edited in /admin/builder.php?page=notfound
if (!isset($lang)) { $lang = 'en'; }
require_once __DIR__ . '/includes/config.php';

// Real 404 status (the admin live preview shows it as a normal page instead).
if (!defined('ENWIRES_PREVIEW') && !headers_sent()) {
    http_response_code(404);
}

$pageNoindex = true;   // keep error pages out of search results

// Browser title = the heading of the first block that has one.
$pageTitle = '404';
foreach (enw_page_blocks('notfound') as $b) {
    if (!empty($b['fields']['heading'])) {
        $pageTitle = html_entity_decode(strip_tags($b['fields']['heading']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        break;
    }
}

require_once __DIR__ . '/includes/header.php';
enw_render_blocks('notfound');
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
