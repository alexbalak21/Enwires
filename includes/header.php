<?php
require_once __DIR__ . '/config.php';
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · ' . SITE_NAME : SITE_NAME; ?></title>
<meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Enwires develops SiBoost, a silicon-graphite composite that increases the energy density of lithium-ion batteries.'; ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
<link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>assets/img/enwires-logo.svg">
<link rel="alternate icon" href="<?php echo BASE_URL; ?>assets/img/logo.png">
<link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>assets/img/logo.png">
</head>
<body>

<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="<?php echo BASE_URL; ?>index.php">
      <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Enwires logo" class="brand-mark">
      <span class="brand-text">
        <span class="brand-name">ENWIRES</span>
        <span class="brand-tagline"><?php echo SITE_TAGLINE; ?></span>
      </span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="Toggle navigation">
      <span></span><span></span><span></span>
    </button>

    <nav class="site-nav" id="siteNav">
      <?php foreach ($NAV_ITEMS as $href => $label): ?>
        <a href="<?php echo BASE_URL . $href; ?>" class="<?php echo $current === $href ? 'is-active' : ''; ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>