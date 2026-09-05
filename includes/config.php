<?php
// Site-wide configuration for Enwires website

define('SITE_NAME', 'ENWIRES');
define('SITE_TAGLINE', 'more energy in your battery');
define('SITE_EMAIL', 'contact@enwires.com');
define('SITE_LOCATION', 'Grenoble, France');
define('SITE_YEAR', date('Y'));

// Used to build canonical URLs and Open Graph tags. Update this once the
// site has a real domain.
define('SITE_URL', 'https://www.enwires.com/');

// Default SEO fallbacks, used on any page that doesn't set its own
// $pageDescription / $pageKeywords before including header.php.
define('DEFAULT_DESCRIPTION', 'Enwires develops SiBoost, a silicon-graphite composite that increases the energy density of lithium-ion batteries.');
define('DEFAULT_KEYWORDS', 'Enwires, SiBoost, silicon graphite composite, battery anode material, lithium-ion battery, energy density, graphite silicon anode, battery materials Grenoble');

// Base path so links work whether the site sits at the domain root or in a subfolder
define('BASE_URL', '');

$NAV_ITEMS = [
    'index.php'   => 'Home',
    'product.php' => 'Product',
    'contact.php' => 'Contact',
];