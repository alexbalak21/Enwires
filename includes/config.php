<?php
// Site-wide configuration for Enwires website

define('SITE_NAME', 'ENWIRES');
define('SITE_EMAIL', 'contact@enwires.com');
define('SITE_LOCATION', 'Grenoble, France'); // kept in Latin script in every language (postal address)
define('SITE_YEAR', date('Y'));

// Used to build canonical URLs and Open Graph tags. Update this once the
// site has a real domain.
define('SITE_URL', 'https://www.enwires.com/');

// Default SEO fallbacks, used on any page that doesn't set its own
// $pageDescription / $pageKeywords before including header.php.
define('DEFAULT_DESCRIPTION', 'Enwires develops SiBoost, a silicon-graphite composite that increases the energy density of lithium-ion batteries.');
define('DEFAULT_KEYWORDS', 'Enwires, SiBoost, silicon graphite composite, battery anode material, lithium-ion battery, energy density, graphite silicon anode, battery materials Grenoble');

// Root-absolute path for CSS/JS/images. These are the same physical files
// for every language, so this never changes based on which language folder
// served the page (e.g. /fr/product.php still loads /assets/css/style.css).
define('ASSETS_URL', '/');

// --- Language / i18n setup -------------------------------------------------
//
// $lang is set by the page BEFORE requiring this file:
//   - root pages (index.php, product.php, contact.php) default to 'en'
//   - /fr/, /zh/, /ja/ wrapper pages set $lang explicitly before requiring
//     the matching root page
if (!isset($lang) || !in_array($lang, ['en', 'fr', 'zh', 'ja'], true)) {
    $lang = 'en';
}
define('CURRENT_LANG', $lang);

// prefix    = URL folder for this language ('' for the default/English root)
// html_lang = BCP47 tag for <html lang="..."> and hreflang links
// og_locale = Open Graph locale format (used by Facebook/LinkedIn previews)
$GLOBALS['LANGUAGES'] = [
    'en' => ['label' => 'EN',   'name' => 'English',  'prefix' => '',    'html_lang' => 'en',      'og_locale' => 'en_US'],
    'zh' => ['label' => '中文', 'name' => '中文',       'prefix' => 'zh/', 'html_lang' => 'zh-Hans', 'og_locale' => 'zh_CN'],
    'ja' => ['label' => 'JA',   'name' => '日本語',      'prefix' => 'ja/', 'html_lang' => 'ja',      'og_locale' => 'ja_JP'],
    'fr' => ['label' => 'FR',   'name' => 'Français',  'prefix' => 'fr/', 'html_lang' => 'fr',      'og_locale' => 'fr_FR'],
];

// Root-absolute path prefix for internal page links in the current
// language, e.g. '/' for English, '/fr/' for French.
define('PAGE_BASE', '/' . $GLOBALS['LANGUAGES'][CURRENT_LANG]['prefix']);

require_once __DIR__ . '/i18n.php'; // defines t()

$NAV_ITEMS = [
    'index.php'   => t('nav.home'),
    'product.php' => t('nav.product'),
    'contact.php' => t('nav.contact'),
];
