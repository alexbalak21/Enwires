<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/users.php';

if (admin_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$rawToken = trim($_GET['token'] ?? '');
$user = password_reset_validate($rawToken);

$messages = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Re-read the raw token from the hidden field so it's still available
    // after a POST (the GET param is gone on form submission).
    $rawToken = trim($_POST['token'] ?? '');
    if (!admin_verify_csrf()) {
        $messages[] = ['type' => 'error', 'text' => 'Your session expired — please try again.'];
    } else {
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($password !== $passwordConfirm) {
            $messages[] = ['type' => 'error', 'text' => 'Passwords do not match.'];
            $user = password_reset_validate($rawToken); // re-validate so we can show the form again
        } else {
            $result = password_reset_consume($rawToken, $password);
            if ($result['ok']) {
                $success = true;
            } else {
                $messages[] = ['type' => 'error', 'text' => $result['error']];
            }
        }
    }
} else {
    // GET request — just validate the token before showing the form.
    if (!$user) {
        $messages[] = ['type' => 'error', 'text' => 'This reset link is invalid or has expired. Please request a new one.'];
    }
}

$pageTitle = 'Reset password';
require __DIR__ . '/includes/layout-header.php';
?>

<div class="login-box">
  <a href="login.php" class="back-link">&larr; Back to login</a>
  <h1>Reset your password</h1>

  <?php if ($success): ?>
    <div class="panel">
      <div class="alert alert-success" style="margin:0;">Your password has been updated successfully.</div>
      <p style="margin:16px 0 0;"><a href="login.php" class="btn-primary">Log in</a></p>
    </div>

  <?php elseif ($user): ?>
    <?php foreach ($messages as $m): ?>
      <div class="alert alert-<?php echo $m['type']; ?>"><?php echo htmlspecialchars($m['text']); ?></div>
    <?php endforeach; ?>
    <div class="panel">
      <p style="margin-top:0; color:var(--ink-dim); font-size:.9rem;">
        Setting a new password for <strong><?php echo htmlspecialchars($user['username']); ?></strong>.
      </p>
      <form method="post">
        <?php echo admin_csrf_field(); ?>
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($rawToken); ?>">
        <div class="field">
          <label class="field-label" for="password">New password</label>
          <input type="password" id="password" name="password" autocomplete="new-password" required autofocus
                 minlength="8" placeholder="At least 8 characters">
        </div>
        <div class="field">
          <label class="field-label" for="password_confirm">Confirm new password</label>
          <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn-primary">Set new password</button>
      </form>
    </div>

  <?php else: ?>
    <?php foreach ($messages as $m): ?>
      <div class="alert alert-<?php echo $m['type']; ?>"><?php echo htmlspecialchars($m['text']); ?></div>
    <?php endforeach; ?>
    <p><a href="forgot-password.php">Request a new reset link</a></p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
