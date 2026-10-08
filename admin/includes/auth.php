<?php
// Session-based auth for the admin panel, backed by the SQLite users table.
// Included by every admin page before any output.

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/users.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/admin/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // uncomment once the admin is served over HTTPS
    ]);
    session_start();
}

function admin_current_user() {
    return $_SESSION['admin_user'] ?? null;
}

function admin_current_user_id() {
    return $_SESSION['admin_user_id'] ?? null;
}

function admin_is_logged_in() {
    return admin_current_user_id() !== null;
}

/**
 * Call at the top of every protected admin page. Redirects to the login
 * page (preserving the originally requested page) if not logged in.
 */
function admin_require_login() {
    if (!admin_is_logged_in()) {
        $returnTo = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: login.php?return=' . urlencode($returnTo));
        exit;
    }
}

/**
 * Attempt to log in by username OR email. Returns true on success (and
 * sets the session), false on failure. Always runs password_verify
 * against *something*, even for an unknown identifier, so failed attempts
 * take a consistent amount of time regardless of whether the account exists.
 */
function admin_attempt_login($identifier, $password) {
    $user = users_find_by_username_or_email(trim($identifier));
    $dummyHash = '$2y$10$abcdefghijklmnopqrstuuJrK8z0h6g3z8z0h6g3z8z0h6g3z8z0h6';
    $ok = password_verify($password, $user['password_hash'] ?? $dummyHash);

    if ($ok && $user) {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = $user['username'];
        $_SESSION['admin_user_id'] = (int) $user['id'];
        return true;
    }
    return false;
}

function admin_logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

// --- CSRF protection -------------------------------------------------------

function admin_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function admin_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(admin_csrf_token()) . '">';
}

function admin_verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
