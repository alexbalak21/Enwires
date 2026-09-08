<?php
require_once __DIR__ . '/config.php';
$current = basename($_SERVER['SCRIPT_NAME']);

// Per-page SEO values, with sane fallbacks. Set $pageTitle / $pageDescription /
// $pageKeywords / $pageImage before including this file to override.
$metaDescription = isset($pageDescription) ? $pageDescription : DEFAULT_DESCRIPTION;
$metaKeywords    = isset($pageKeywords) ? $pageKeywords : DEFAULT_KEYWORDS;
$metaTitle       = isset($pageTitle) ? $pageTitle . ' · ' . SITE_NAME . ' — ' . t('site.tagline') : SITE_NAME . ' — ' . t('site.tagline');

// Build this page's URL in every language, for the canonical link and the
// hreflang alternate tags (both need root-absolute, full URLs).
$pagePath = ($current === 'index.php') ? '' : $current;
$alternateUrls = [];
foreach ($GLOBALS['LANGUAGES'] as $code => $meta) {
    $alternateUrls[$code] = rtrim(SITE_URL, '/') . '/' . $meta['prefix'] . $pagePath;
}
$canonicalUrl = $alternateUrls[CURRENT_LANG];
$ogImage      = rtrim(SITE_URL, '/') . '/' . (isset($pageImage) ? $pageImage : 'assets/img/bottles.jpg');
?>
<!DOCTYPE html>
<html lang="<?php echo $GLOBALS['LANGUAGES'][CURRENT_LANG]['html_lang']; ?>">
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

<!-- hreflang: tells search engines which language/URL to show for each visitor -->
<?php foreach ($alternateUrls as $code => $url): ?>
<link rel="alternate" hreflang="<?php echo $GLOBALS['LANGUAGES'][$code]['html_lang']; ?>" href="<?php echo htmlspecialchars($url); ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars($alternateUrls['en']); ?>">

<!-- Open Graph (Facebook, LinkedIn, etc.) -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($metaTitle); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
<meta property="og:locale" content="<?php echo $GLOBALS['LANGUAGES'][CURRENT_LANG]['og_locale']; ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($metaTitle); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo ASSETS_URL; ?>assets/css/style.css">
<link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>assets/img/enwires-logo.svg">
<link rel="alternate icon" href="<?php echo ASSETS_URL; ?>assets/img/logo.png">
<link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>assets/img/logo.png">
</head>
<body>

<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="<?php echo PAGE_BASE; ?>">
      <img src="<?php echo ASSETS_URL; ?>assets/img/logo.png" alt="<?php echo htmlspecialchars(t('nav.logo_alt')); ?>" class="brand-mark">
      <span class="brand-text">
        <span class="brand-name">ENWIRES</span>
        <span class="brand-tagline"><?php echo t('site.tagline'); ?></span>
      </span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="<?php echo htmlspecialchars(t('nav.toggle_aria')); ?>">
      <span></span><span></span><span></span>
    </button>

    <nav class="site-nav" id="siteNav">
      <?php foreach ($NAV_ITEMS as $file => $label): ?>
        <?php $navHref = ($file === 'index.php') ? PAGE_BASE : PAGE_BASE . $file; ?>
        <a href="<?php echo $navHref; ?>" class="<?php echo $current === $file ? 'is-active' : ''; ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>

      <span class="lang-switch">
        <?php foreach ($GLOBALS['LANGUAGES'] as $code => $meta): ?>
          <?php if ($code === CURRENT_LANG): ?>
            <span class="lang-switch-current" aria-current="true"><?php echo $meta['label']; ?></span>
          <?php else: ?>
            <?php $switchHref = ($current === 'index.php') ? '/' . $meta['prefix'] : '/' . $meta['prefix'] . $current; ?>
            <a href="<?php echo $switchHref; ?>" hreflang="<?php echo $meta['html_lang']; ?>" lang="<?php echo $meta['html_lang']; ?>"><?php echo $meta['label']; ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </span>
    </nav>
  </div>
</header>
