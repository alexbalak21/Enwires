<?php
require_once __DIR__ . '/config.php';

// Header / footer content (brand, links, ...) comes from the admin: /admin/layout.php
$L = enw_layout();

// Which page is being shown: set by router.php (or the admin live preview).
$pageKey  = enw_current_page_key();
$pageSlug = enw_page_slug($pageKey);

// Remember whatever language is actually being shown on this page — whether
// that's from browser-language detection, a manual click on the language
// switcher, or a direct/shared link to a specific language URL. This is
// what includes/language-detect.php checks on a later visit to "/", so a
// manual choice always wins over the original auto-detection.
if (!headers_sent() && !defined('ENWIRES_PREVIEW')) {
    setcookie('enwires_lang', CURRENT_LANG, time() + 60 * 60 * 24 * 365, '/');
}

// Per-page SEO values, with sane fallbacks. Set $pageTitle / $pageDescription /
// $pageKeywords / $pageImage before including this file to override.
// Set $pageNoindex = true for pages that must stay out of search results (404).
$metaDescription = isset($pageDescription) ? $pageDescription : DEFAULT_DESCRIPTION;
$metaKeywords    = isset($pageKeywords) ? $pageKeywords : DEFAULT_KEYWORDS;
$brandName       = $L['brand']['name'];
$brandLine       = $brandName . ($L['brand']['tagline'] !== '' ? ' — ' . $L['brand']['tagline'] : '');
$metaTitle       = isset($pageTitle) ? $pageTitle . ' · ' . $brandLine : $brandLine;
$noindex         = !empty($pageNoindex);

// This page's URL in every language, for the canonical link and the hreflang
// alternate tags (both need root-absolute, full URLs). Clean URLs: /product, /fr/product ...
$alternateUrls = [];
foreach ($GLOBALS['LANGUAGES'] as $code => $meta) {
    $alternateUrls[$code] = rtrim(SITE_URL, '/') . '/' . $meta['prefix'] . $pageSlug;
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
<meta name="robots" content="<?php echo $noindex ? 'noindex, follow' : 'index, follow'; ?>">
<meta name="author" content="Enwires">
<?php if (!$noindex): ?>
<link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">

<!-- hreflang: tells search engines which language/URL to show for each visitor -->
<?php foreach ($alternateUrls as $code => $url): ?>
<link rel="alternate" hreflang="<?php echo $GLOBALS['LANGUAGES'][$code]['html_lang']; ?>" href="<?php echo htmlspecialchars($url); ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars($alternateUrls['en']); ?>">
<?php endif; ?>

<!-- Open Graph (Facebook, LinkedIn, etc.) -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo htmlspecialchars($brandName); ?>">
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
      <?php if ($L['brand']['logo'] !== ''): ?>
      <img src="<?php echo enw_img_src($L['brand']['logo']); ?>" alt="<?php echo enw_attr($L['brand']['logo_alt']); ?>" class="brand-mark">
      <?php endif; ?>
      <span class="brand-text">
        <span class="brand-name"><?php echo htmlspecialchars($brandName); ?></span>
        <?php if ($L['brand']['tagline'] !== ''): ?>
        <span class="brand-tagline"><?php echo enw_rich($L['brand']['tagline']); ?></span>
        <?php endif; ?>
      </span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="<?php echo enw_attr($L['toggle_aria'] !== '' ? $L['toggle_aria'] : 'Menu'); ?>">
      <span></span><span></span><span></span>
    </button>

    <nav class="site-nav" id="siteNav">
      <?php foreach ($L['nav'] as $item): ?>
        <?php $isActive = ($item['kind'] === 'page' && $item['page'] === $pageKey); ?>
        <a href="<?php echo enw_attr(enw_nav_href($item)); ?>"<?php echo $isActive ? ' class="is-active"' : ''; ?><?php echo enw_nav_target($item); ?>><?php echo enw_rich(enw_nav_label($item)); ?></a>
      <?php endforeach; ?>

      <?php if ($L['show_lang_switch']): ?>
      <span class="lang-switch">
        <?php foreach ($GLOBALS['LANGUAGES'] as $code => $meta): ?>
          <?php if ($code === CURRENT_LANG): ?>
            <span class="lang-switch-current" aria-current="true"><?php echo $meta['label']; ?></span>
          <?php else: ?>
            <a href="/set-lang.php?lang=<?php echo urlencode($code); ?>&amp;to=<?php echo urlencode($pageSlug); ?>" hreflang="<?php echo $meta['html_lang']; ?>" lang="<?php echo $meta['html_lang']; ?>"><?php echo $meta['label']; ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </span>
      <?php endif; ?>
    </nav>
  </div>
</header>
