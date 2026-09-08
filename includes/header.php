<?php
require_once __DIR__ . '/config.php';
$current = basename($_SERVER['SCRIPT_NAME']);
if ($current === 'index.php') {
  $current = '';
}

// Per-page SEO values, with sane fallbacks. Set $pageTitle / $pageDescription /
// $pageKeywords / $pageImage before including this file to override.
$metaDescription = isset($pageDescription) ? $pageDescription : DEFAULT_DESCRIPTION;
$metaKeywords    = isset($pageKeywords) ? $pageKeywords : DEFAULT_KEYWORDS;
$metaTitle       = isset($pageTitle) ? $pageTitle . ' · ' . SITE_NAME . ' — ' . SITE_TAGLINE : SITE_NAME . ' — ' . SITE_TAGLINE;
$canonicalUrl    = rtrim(SITE_URL, '/') . '/' . $current;
$ogImage         = rtrim(SITE_URL, '/') . '/' . (isset($pageImage) ? $pageImage : 'assets/img/bottles.jpg');

// Build a working href for each nav item. An empty key ('' = Home) must not
// resolve to an empty href, since that just points back at the current page.
function nav_href($href) {
    if ($href === '') {
        return BASE_URL !== '' ? BASE_URL : './';
    }
    return BASE_URL . $href;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($metaTitle); ?></title>

  <!-- Core SEO -->
  <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
  <meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords); ?>">
  <meta name="robots" content="index, follow">
  <meta name="author" content="Enwires">
  <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">

  <!-- Open Graph (Facebook, LinkedIn, etc.) -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
  <meta property="og:title" content="<?php echo htmlspecialchars($metaTitle); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
  <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
  <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
  <meta property="og:locale" content="en_US">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($metaTitle); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
  <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">

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
      <a class="brand" href="<?php echo BASE_URL !== '' ? BASE_URL : './'; ?>">
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
          <a href="<?php echo nav_href($href); ?>" class="<?php echo $current === $href ? 'is-active' : ''; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </header>