<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/includes/users.php';

$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!admin_verify_csrf()) {
        $messages[] = ['type' => 'error', 'text' => 'Your session expired — please try again.'];
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === admin_current_user_id()) {
            $messages[] = ['type' => 'error', 'text' => "You can't delete your own account while logged in as it."];
        } else {
            $result = users_delete($id);
            if ($result['ok']) {
                $messages[] = ['type' => 'success', 'text' => 'Account deleted.'];
            } else {
                $messages[] = ['type' => 'error', 'text' => $result['error']];
            }
        }
    }
}

$users = users_all();
$pageTitle = 'Admin users';
require __DIR__ . '/includes/layout-header.php';
?>

<a href="index.php" class="back-link">&larr; Back to dashboard</a>
<h1>Admin users</h1>
<p class="subtitle">Everyone who can log in and edit the site.</p>

<?php foreach ($messages as $m): ?>
  <div class="alert alert-<?php echo $m['type']; ?>"><?php echo htmlspecialchars($m['text']); ?></div>
<?php endforeach; ?>

<div class="panel">
  <table class="users-table">
    <thead>
      <tr><th>Username</th><th>Email</th><th>Last updated</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?php echo htmlspecialchars($u['username']); ?><?php if ((int)$u['id'] === admin_current_user_id()): ?> <span class="you-tag">(you)</span><?php endif; ?></td>
        <td><?php echo htmlspecialchars($u['email']); ?></td>
        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($u['updated_at']))); ?></td>
        <td class="users-table-actions">
          <a href="user-edit.php?id=<?php echo (int) $u['id']; ?>">Edit</a>
          <?php if (count($users) > 1): ?>
          <form method="post" onsubmit="return confirm('Delete this admin account?');" style="display:inline;">
            <?php echo admin_csrf_field(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
            <button type="submit" class="link-button">Delete</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<a href="user-edit.php" class="btn-primary">+ Add admin user</a>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
