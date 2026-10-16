<?php
// AJAX endpoint: save the header / footer settings in one language.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout-store.php';

bs_require_login_json();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bs_json(['ok' => false, 'error' => 'POST only.'], 405);
}
if (!bs_verify_csrf_header()) {
    bs_json(['ok' => false, 'error' => 'Your session expired. Reload the page (copy any unsaved text first).'], 403);
}

$body = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($body) || !isset($body['lang'], $body['draft']) || !is_array($body['draft'])) {
    bs_json(['ok' => false, 'error' => 'Invalid request.'], 400);
}

$result = ls_save((string) $body['lang'], $body['draft'], (string) ($body['rev'] ?? ''));
bs_json($result, $result['ok'] ? 200 : (!empty($result['conflict']) ? 409 : 400));
