<?php
// $pageTitle should be set before including this.
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?php echo htmlspecialchars($pageTitle ?? 'Admin'); ?> · Enwires Admin</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<header class="admin-header">
  <div class="admin-header-inner">
    <a href="index.php" class="admin-brand">Enwires <span>Admin</span></a>
    <?php if (admin_is_logged_in()): ?>
    <nav class="admin-topnav">
      <a href="users.php">Users</a>
      <a href="user-edit.php?id=<?php echo admin_current_user_id(); ?>">My account</a>
      <span class="admin-topnav-sep">|</span>
      <a href="logout.php">Log out</a>
    </nav>
    <?php endif; ?>
  </div>
</header>
<main class="admin-main">
