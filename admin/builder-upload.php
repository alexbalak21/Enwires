<?php
// AJAX endpoint: upload one image for the block builder. Returns {ok, src}.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/blocks-store.php';

bs_require_login_json();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bs_json(['ok' => false, 'error' => 'POST only.'], 405);
}
if (!admin_verify_csrf()) {
    bs_json(['ok' => false, 'error' => 'Your session expired. Reload the page.'], 403);
}

$result = handle_image_upload($_FILES['file'] ?? null);
if ($result === null) {
    bs_json(['ok' => false, 'error' => 'No file received. (If the image is large, the server limit upload_max_filesize may be too small.)'], 400);
}
if (is_upload_error($result)) {
    bs_json(['ok' => false, 'error' => $result['error']], 400);
}
bs_json(['ok' => true, 'src' => $result, 'library' => bs_image_library(!empty($_POST['logos']))]);
