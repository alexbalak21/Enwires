<?php
// AJAX endpoint: save one page in one language from the block builder.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/blocks-store.php';

bs_require_login_json();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bs_json(['ok' => false, 'error' => 'POST only.'], 405);
}
if (!bs_verify_csrf_header()) {
    bs_json(['ok' => false, 'error' => 'Your session expired. Reload the page (copy any unsaved text first).'], 403);
}

$body = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($body) || !isset($body['page'], $body['lang'], $body['blocks']) || !is_array($body['blocks'])) {
    bs_json(['ok' => false, 'error' => 'Invalid request.'], 400);
}

$result = bs_save_page(
    (string) $body['page'],
    (string) $body['lang'],
    $body['blocks'],
    (isset($body['meta']) && is_array($body['meta'])) ? $body['meta'] : [],
    (string) ($body['rev'] ?? '')
);

bs_json($result, $result['ok'] ? 200 : (!empty($result['conflict']) ? 409 : 500));
