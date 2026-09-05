<?php
// Site-wide configuration for Enwires website

define('SITE_NAME', 'ENWIRES');
define('SITE_TAGLINE', 'more energy in your battery');
define('SITE_EMAIL', 'contact@enwires.com');
define('SITE_LOCATION', 'Grenoble, France');
define('SITE_YEAR', date('Y'));

// Base path so links work whether the site sits at the domain root or in a subfolder
define('BASE_URL', '');

$NAV_ITEMS = [
    'index.php'   => 'Home',
    'product.php' => 'Product',
    'contact.php' => 'Contact',
];
