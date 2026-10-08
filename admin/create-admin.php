<?php
// CLI-only tool to create (or update) an admin account in the SQLite
// database. Mainly meant for initial setup — once at least one account
// exists, use /admin/users.php (while logged in) to manage accounts day
// to day, including resetting a password.
//
// Usage:
//   php admin/create-admin.php <username> <email> <password>
//
// Running it again with an existing username updates that account
// (new email and/or password) rather than creating a duplicate.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/users.php';

if ($argc < 4) {
    fwrite(STDERR, "Usage: php create-admin.php <username> <email> <password>\n");
    exit(1);
}

[$script, $username, $email, $password] = $argv;

$existing = users_find_by_username_or_email($username);
if ($existing) {
    $result = users_update($existing['id'], $username, $email, $password);
    $verb = 'updated';
} else {
    $result = users_create($username, $email, $password);
    $verb = 'created';
}

if (!$result['ok']) {
    fwrite(STDERR, "Error: {$result['error']}\n");
    exit(1);
}

echo "Admin account '$username' $verb.\n";
