<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/users.php';
require_once __DIR__ . '/includes/mailer.php';

if (admin_is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Site URL for the reset link — update this once you have a real domain.
// Must match the public URL of the admin folder exactly.
define('ADMIN_URL', 'http://localhost:8000/admin-password-reset');

$submitted = false;
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf()) {
        $messages[] = ['type' => 'error', 'text' => 'Your session expired — please try again.'];
    } else {
        $email = trim($_POST['email'] ?? '');

        // Always show the same "check your email" message whether the
        // address exists or not — avoids leaking which emails are registered.
        $user = users_find_by_email($email);
        if ($user) {
            $rawToken = password_reset_create((int) $user['id']);
            $resetLink = ADMIN_URL . '/reset-password.php?token=' . urlencode($rawToken);
            send_password_reset_email($user['email'], $user['username'], $resetLink);
        }
        $submitted = true;
    }
}

$pageTitle = 'Forgot password';
require __DIR__ . '/includes/layout-header.php';
?>

<div class="login-box">
  <a href="login.php" class="back-link">&larr; Back to login</a>
  <h1>Forgot your password?</h1>

  <?php if ($submitted): ?>
    <div class="panel">
      <div class="alert alert-success" style="margin:0;">
        If that email address is registered, a reset link has been sent to it.
        Check your inbox (and spam folder) — the link expires in
        <?php echo RESET_TOKEN_TTL_MINUTES; ?> minutes.
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($messages as $m): ?>
      <div class="alert alert-<?php echo $m['type']; ?>"><?php echo htmlspecialchars($m['text']); ?></div>
    <?php endforeach; ?>
    <div class="panel">
      <p style="margin-top:0; color:var(--ink-dim); font-size:.9rem;">
        Enter your admin email address and we'll send you a link to reset your password.
      </p>
      <form method="post">
        <?php echo admin_csrf_field(); ?>
        <div class="field">
          <label class="field-label" for="email">Email address</label>
          <input type="email" id="email" name="email" autocomplete="email" required autofocus>
        </div>
        <button type="submit" class="btn-primary">Send reset link</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
