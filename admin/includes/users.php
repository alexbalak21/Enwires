<?php
// User account storage (SQLite) and password-reset tokens.
require_once __DIR__ . '/db.php';

// --- Users ------------------------------------------------------------

function users_all() {
    return db()->query('SELECT id, username, email, created_at, updated_at FROM users ORDER BY username')->fetchAll();
}

function users_count() {
    return (int) db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
}

function users_find_by_id($id) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function users_find_by_username_or_email($identifier) {
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$identifier, $identifier]);
    return $stmt->fetch() ?: null;
}

function users_find_by_email($email) {
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

/**
 * Returns ['ok' => true, 'id' => ...] or ['ok' => false, 'error' => '...'].
 */
function users_create($username, $email, $password) {
    $username = trim($username);
    $email = trim($email);

    $error = users_validate_fields($username, $email, $password, null);
    if ($error) {
        return ['ok' => false, 'error' => $error];
    }

    $now = date('c');
    $stmt = db()->prepare('INSERT INTO users (username, email, password_hash, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
    try {
        $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $now, $now]);
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => 'That username or email is already in use.'];
    }
    return ['ok' => true, 'id' => (int) db()->lastInsertId()];
}

/**
 * $password may be null/empty to leave the password unchanged.
 */
function users_update($id, $username, $email, $password = null) {
    $username = trim($username);
    $email = trim($email);

    $error = users_validate_fields($username, $email, $password, $id);
    if ($error) {
        return ['ok' => false, 'error' => $error];
    }

    $now = date('c');
    if ($password) {
        $stmt = db()->prepare('UPDATE users SET username = ?, email = ?, password_hash = ?, updated_at = ? WHERE id = ?');
        $params = [$username, $email, password_hash($password, PASSWORD_DEFAULT), $now, $id];
    } else {
        $stmt = db()->prepare('UPDATE users SET username = ?, email = ?, updated_at = ? WHERE id = ?');
        $params = [$username, $email, $now, $id];
    }
    try {
        $stmt->execute($params);
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => 'That username or email is already in use.'];
    }
    return ['ok' => true];
}

function users_delete($id) {
    if (users_count() <= 1) {
        return ['ok' => false, 'error' => "Can't delete the only remaining admin account."];
    }
    $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return ['ok' => true];
}

function users_validate_fields($username, $email, $password, $excludeId) {
    if ($username === '' || strlen($username) < 3) {
        return 'Username must be at least 3 characters.';
    }
    if (!preg_match('/^[a-zA-Z0-9_.\-]+$/', $username)) {
        return 'Username can only contain letters, numbers, dots, dashes, and underscores.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    if ($password !== null && $password !== '' && strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if ($excludeId === null && ($password === null || $password === '')) {
        return 'Password is required for a new account.';
    }
    return null;
}

// --- Password reset tokens ---------------------------------------------

const RESET_TOKEN_TTL_MINUTES = 60;

/**
 * Creates a reset token for the given user and returns the RAW token
 * (only ever returned here, never stored — the DB only holds its hash).
 * Also invalidates any previous outstanding tokens for that user.
 */
function password_reset_create($userId) {
    $pdo = db();
    $pdo->prepare('UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL')
        ->execute([date('c'), $userId]);

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = date('c', time() + RESET_TOKEN_TTL_MINUTES * 60);

    $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $tokenHash, $expiresAt, date('c')]);

    return $rawToken;
}

/**
 * Returns the user row for a valid (unexpired, unused) raw token, or null.
 */
function password_reset_validate($rawToken) {
    if (!is_string($rawToken) || $rawToken === '') {
        return null;
    }
    $tokenHash = hash('sha256', $rawToken);
    $stmt = db()->prepare('
        SELECT u.* FROM password_resets pr
        JOIN users u ON u.id = pr.user_id
        WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > ?
    ');
    $stmt->execute([$tokenHash, date('c')]);
    return $stmt->fetch() ?: null;
}

/**
 * Consumes a valid token: sets the user's new password and marks the
 * token (and any other outstanding ones for that user) used.
 */
function password_reset_consume($rawToken, $newPassword) {
    $user = password_reset_validate($rawToken);
    if (!$user) {
        return ['ok' => false, 'error' => 'This reset link is invalid or has expired.'];
    }
    if (strlen($newPassword) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    $pdo = db();
    $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?')
        ->execute([password_hash($newPassword, PASSWORD_DEFAULT), date('c'), $user['id']]);
    $pdo->prepare('UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL')
        ->execute([date('c'), $user['id']]);

    return ['ok' => true, 'username' => $user['username']];
}
