<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/includes/users.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isNew = $id === null;
$user = null;

if (!$isNew) {
    $user = users_find_by_id($id);
    if (!$user) {
        header('Location: users.php');
        exit;
    }
}

$values = [
    'username' => $user['username'] ?? '',
    'email'    => $user['email'] ?? '',
];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf()) {
        $messages[] = ['type' => 'error', 'text' => 'Your session expired — please try again.'];
    } else {
        $values['username'] = trim($_POST['username'] ?? '');
        $values['email']    = trim($_POST['email'] ?? '');
        $password           = $_POST['password'] ?? '';
        $passwordConfirm    = $_POST['password_confirm'] ?? '';

        if ($password !== '' && $password !== $passwordConfirm) {
            $messages[] = ['type' => 'error', 'text' => 'Passwords do not match.'];
        } else {
            if ($isNew) {
                $result = users_create($values['username'], $values['email'], $password);
            } else {
                $result = users_update($id, $values['username'], $values['email'], $password ?: null);
            }

            if ($result['ok']) {
                // If the currently-logged-in admin just changed their own
                // username, refresh the session so the header still shows
                // the right name.
                if (!$isNew && $id === admin_current_user_id()) {
                    $_SESSION['admin_user'] = $values['username'];
                }
                header('Location: users.php?saved=1');
                exit;
            }
            $messages[] = ['type' => 'error', 'text' => $result['error']];
        }
    }
}

$pageTitle = $isNew ? 'Add admin user' : 'Edit ' . ($user['username'] ?? '');
require __DIR__ . '/includes/layout-header.php';
?>

<a href="users.php" class="back-link">&larr; Back to users</a>
<h1><?php echo $isNew ? 'Add admin user' : 'Edit ' . htmlspecialchars($user['username']); ?></h1>

<?php if (isset($_GET['saved'])): ?>
  <div class="alert alert-success">Saved.</div>
<?php endif; ?>
<?php foreach ($messages as $m): ?>
  <div class="alert alert-<?php echo $m['type']; ?>"><?php echo htmlspecialchars($m['text']); ?></div>
<?php endforeach; ?>

<div class="panel">
  <form method="post">
    <?php echo admin_csrf_field(); ?>

    <div class="field">
      <label class="field-label" for="username">Username</label>
      <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($values['username']); ?>" autocomplete="username" required>
    </div>

    <div class="field">
      <label class="field-label" for="email">Email address</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($values['email']); ?>" autocomplete="email" required>
    </div>

    <div class="field">
      <label class="field-label" for="password">
        <?php echo $isNew ? 'Password' : 'New password'; ?>
        <?php if (!$isNew): ?><span class="field-hint">(leave blank to keep the current password)</span><?php endif; ?>
      </label>
      <input type="password" id="password" name="password" autocomplete="new-password" <?php echo $isNew ? 'required' : ''; ?>>
    </div>

    <div class="field">
      <label class="field-label" for="password_confirm">Confirm password</label>
      <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password">
    </div>

    <div class="form-actions" style="position:static; border-top:none; padding-top:8px;">
      <button type="submit" class="btn-primary"><?php echo $isNew ? 'Create account' : 'Save changes'; ?></button>
      <a href="users.php" class="btn-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
