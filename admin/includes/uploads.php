<?php
// Image upload validation + storage for the admin panel.

define('UPLOAD_DIR', dirname(__DIR__, 2) . '/assets/img');
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024); // 10MB

const ALLOWED_MIME_EXT = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

/**
 * Validate and store an uploaded file (one entry of $_FILES). Returns the
 * new filename (string) on success, or a WP_Error-ish array
 * ['error' => 'message'] on failure. Returns null if no file was actually
 * uploaded in this slot (not an error — most form submits won't include a
 * new file for most rows).
 */
function handle_image_upload($fileEntry) {
    if (!is_array($fileEntry) || !isset($fileEntry['error'])) {
        return null;
    }
    if ($fileEntry['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // nothing chosen for this row — not an error
    }
    if ($fileEntry['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload failed (error code ' . $fileEntry['error'] . ').'];
    }
    if ($fileEntry['size'] > MAX_UPLOAD_BYTES) {
        return ['error' => 'Image is too large (max 10MB).'];
    }
    if (!is_uploaded_file($fileEntry['tmp_name'])) {
        return ['error' => 'Upload failed validation.'];
    }

    // Trust the file's actual bytes, not the extension or the
    // browser-supplied Content-Type — both are trivially spoofable.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($fileEntry['tmp_name']);

    if (!isset(ALLOWED_MIME_EXT[$mime])) {
        return ['error' => 'Unsupported file type (' . htmlspecialchars($mime) . '). Use JPG, PNG, or WebP.'];
    }
    $ext = ALLOWED_MIME_EXT[$mime];

    // Re-encode through GD rather than just copying the uploaded bytes —
    // this strips anything malicious embedded outside the actual image
    // data (a common trick for disguising non-image payloads as images)
    // and guarantees the file on disk is genuinely a well-formed image of
    // the claimed type.
    $image = @imagecreatefromstring(file_get_contents($fileEntry['tmp_name']));
    if ($image === false) {
        return ['error' => 'The file could not be read as a valid image.'];
    }

    $base = pathinfo($fileEntry['name'], PATHINFO_FILENAME);
    $safe = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $base));
    $safe = trim($safe, '-');
    if ($safe === '') {
        $safe = 'image';
    }
    $filename = $safe . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0750, true);
    }
    $destination = UPLOAD_DIR . '/' . $filename;

    $saved = false;
    switch ($ext) {
        case 'jpg':
            $saved = imagejpeg($image, $destination, 88);
            break;
        case 'png':
            $saved = imagepng($image, $destination, 6);
            break;
        case 'webp':
            $saved = function_exists('imagewebp') ? imagewebp($image, $destination, 88) : false;
            break;
    }
    imagedestroy($image);

    if (!$saved) {
        return ['error' => 'Could not save the uploaded image.'];
    }

    return $filename;
}

function is_upload_error($result) {
    return is_array($result) && isset($result['error']);
}

// --- Helpers for reading $_FILES entries posted with our bracket-naming
//     convention: name="upload[<dot.path>][<index>]" for gallery rows, and
//     name="upload_single[<dot.path>]" for single-image fields. ---

function get_uploaded_gallery_file($pathKey, $index) {
    if (!isset($_FILES['upload'])) return null;
    return [
        'name'     => $_FILES['upload']['name'][$pathKey][$index] ?? null,
        'type'     => $_FILES['upload']['type'][$pathKey][$index] ?? null,
        'tmp_name' => $_FILES['upload']['tmp_name'][$pathKey][$index] ?? null,
        'error'    => $_FILES['upload']['error'][$pathKey][$index] ?? UPLOAD_ERR_NO_FILE,
        'size'     => $_FILES['upload']['size'][$pathKey][$index] ?? 0,
    ];
}

function get_uploaded_single_file($pathKey) {
    if (!isset($_FILES['upload_single'])) return null;
    return [
        'name'     => $_FILES['upload_single']['name'][$pathKey] ?? null,
        'type'     => $_FILES['upload_single']['type'][$pathKey] ?? null,
        'tmp_name' => $_FILES['upload_single']['tmp_name'][$pathKey] ?? null,
        'error'    => $_FILES['upload_single']['error'][$pathKey] ?? UPLOAD_ERR_NO_FILE,
        'size'     => $_FILES['upload_single']['size'][$pathKey] ?? 0,
    ];
}
