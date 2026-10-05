<?php
require_once __DIR__ . '/includes/auth.php';

if (admin_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf()) {
        $error = 'Your session expired — please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        if (admin_attempt_login($username, $password)) {
            $returnTo = $_POST['return'] ?? 'index.php';
            // Only ever redirect to a path within this admin folder.
            if (!is_string($returnTo) || $returnTo === '' || $returnTo[0] !== '/') {
                $returnTo = 'index.php';
            } else {
                $returnTo = basename($returnTo) ?: 'index.php';
            }
            header('Location: ' . $returnTo);
            exit;
        }
        $error = 'Incorrect username or password.';
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/layout-header.php';
?>

<div class="login-box panel">
  <h1>Enwires Admin</h1>
  <p class="subtitle">Log in to edit the site's content.</p>

  <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post">
    <?php echo admin_csrf_field(); ?>
    <input type="hidden" name="return" value="<?php echo htmlspecialchars($_GET['return'] ?? 'index.php'); ?>">
    <div class="field">
      <label class="field-label" for="username">Username</label>
      <input type="text" id="username" name="username" autocomplete="username" required autofocus>
    </div>
    <div class="field">
      <label class="field-label" for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn-primary">Log in</button>
  </form>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
