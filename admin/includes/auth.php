<?php
// Session-based auth for the admin panel. Included by every admin page
// before any output.

if (session_status() !== PHP_SESSION_ACTIVE) {
    // Harden the session cookie a little beyond PHP's defaults.
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/admin/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // uncomment once the admin is served over HTTPS
    ]);
    session_start();
}

define('ADMIN_USERS_FILE', dirname(__DIR__, 2) . '/data/admin-users.php');

function admin_load_users() {
    if (!is_file(ADMIN_USERS_FILE)) {
        return [];
    }
    $users = require ADMIN_USERS_FILE;
    return is_array($users) ? $users : [];
}

function admin_current_user() {
    return $_SESSION['admin_user'] ?? null;
}

function admin_is_logged_in() {
    return admin_current_user() !== null;
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
 * Attempt to log in. Returns true on success (and sets the session), false
 * on failure. Intentionally takes the same amount of time whether the
 * username exists or not, to avoid leaking which usernames are valid via
 * response timing.
 */
function admin_attempt_login($username, $password) {
    $users = admin_load_users();
    $hash = $users[$username]['password_hash'] ?? null;

    // Always run password_verify against *something*, even for an unknown
    // username, so failed attempts take a consistent amount of time.
    $dummyHash = '$2y$10$abcdefghijklmnopqrstuuJrK8z0h6g3z8z0h6g3z8z0h6g3z8z0h6';
    $ok = password_verify($password, $hash ?? $dummyHash);

    if ($ok && $hash !== null) {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = $username;
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
