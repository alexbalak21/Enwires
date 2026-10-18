<?php
// Header / footer ("layout") data layer. No dependency on the current page
// language, so the public site, the admin editor and the live preview share it.
//
// Same idea as the blocks — "shared structure, translated text":
//
//   content/site.json        what every language shares
//       brand name, logo, nav links (order, targets), footer options,
//       footer e-mail / location, contact-form recipient
//
//   content/{lang}.json      the translated texts, under "layout"
//       tagline, logo_alt, toggle_aria, copyright,
//       nav: { <item id>: { label } }, footer_nav: { <item id>: { label } }
//
// Nothing has to be saved for the site to work: until site.json exists,
// the defaults below are used and the labels come from the old nav.* /
// site.tagline keys, so the pages look exactly as before.

require_once __DIR__ . '/blocks-core.php';

define('ENW_SITE_FILE', ENW_CONTENT_DIR . '/site.json');
define('ENW_DEFAULT_COPYRIGHT', 'ENWIRES &mdash; {year}');
define('ENW_MAX_NAV_ITEMS', 12);

function enw_site_load() {
    if (!is_file(ENW_SITE_FILE)) {
        return [];
    }
    $data = json_decode((string) file_get_contents(ENW_SITE_FILE), true);
    return is_array($data) ? $data : [];
}

/** Pages a navigation link can point to (key => label): those that have a URL. */
function enw_nav_page_options() {
    $out = [];
    foreach (enw_site_pages() as $key => $p) {
        if ($p['slug'] !== null) {
            $out[$key] = $p['label'];
        }
    }
    return $out;
}

/** A constant from config.php (loading it if needed), or a fallback. */
function enw_site_const($name, $default) {
    if (!defined($name)) {
        $file = __DIR__ . '/config.php';
        if (is_file($file)) {
            global $lang;
            if (!isset($lang)) {
                $lang = 'en';
            }
            require_once $file;
        }
    }
    return defined($name) ? constant($name) : $default;
}

function enw_layout_default_shared() {
    // The built-in defaults are the SITE_EMAIL / SITE_LOCATION set in includes/config.php.
    $email = (string) enw_site_const('SITE_EMAIL', 'contact@enwires.com');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = 'contact@enwires.com';
    }
    $location = (string) enw_site_const('SITE_LOCATION', 'Grenoble, France');
    return [
        'brand_name'        => 'ENWIRES',
        'logo'              => 'logo.png',
        'show_lang_switch'  => true,
        'nav' => [
            ['id' => 'n_home',    'kind' => 'page', 'page' => 'home',    'url' => '', 'new_tab' => false],
            ['id' => 'n_product', 'kind' => 'page', 'page' => 'product', 'url' => '', 'new_tab' => false],
            ['id' => 'n_contact', 'kind' => 'page', 'page' => 'contact', 'url' => '', 'new_tab' => false],
        ],
        'footer_same_nav'   => true,
        'footer_nav'        => [],
        'footer_location'   => $location,
        'footer_email'      => $email,
        'contact_recipient' => $email,
    ];
}

/** Old label keys, used as the starting text of the three original links. */
function enw_layout_legacy_nav_paths() {
    return ['n_home' => 'nav.home', 'n_product' => 'nav.product', 'n_contact' => 'nav.contact'];
}

// --- cleaning -------------------------------------------------------------

function enw_layout_str($v, $max) {
    if (!is_string($v)) return '';
    $v = str_replace(["\r\n", "\r", "\n", "\0"], ' ', $v);
    $v = function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max);
    return trim($v);
}

/** Only allow links that are safe in an href. Returns '' for anything else. */
function enw_clean_url($u) {
    $u = enw_layout_str($u, 500);
    if ($u === '') return '';
    if (preg_match('~^(https?://|mailto:|tel:)~i', $u)) return $u;
    if ($u[0] === '#') return $u;
    if ($u[0] === '/' && substr($u, 0, 2) !== '//') return $u;
    return '';
}

/**
 * Clean a list of nav items. Returns [list without labels, labels by id].
 * Labels are translated texts, so they are kept apart from the shared list.
 */
function enw_layout_clean_items($items, $idPrefix) {
    $pages = enw_nav_page_options();
    $list = [];
    $labels = [];
    $seen = [];
    if (!is_array($items)) {
        return [$list, $labels];
    }
    foreach (array_slice(array_values($items), 0, ENW_MAX_NAV_ITEMS) as $it) {
        if (!is_array($it)) continue;
        $id = $it['id'] ?? '';
        if (!enw_valid_id($id) || isset($seen[$id])) {
            $id = enw_new_id($idPrefix);
        }
        $seen[$id] = true;
        $kind = (($it['kind'] ?? '') === 'url') ? 'url' : 'page';
        $page = (is_string($it['page'] ?? null) && isset($pages[$it['page']])) ? $it['page'] : 'home';
        $list[] = [
            'id'      => $id,
            'kind'    => $kind,
            'page'    => $page,
            'url'     => ($kind === 'url') ? enw_clean_url($it['url'] ?? '') : '',
            'new_tab' => !empty($it['new_tab']),
        ];
        $labels[$id] = enw_layout_str($it['label'] ?? '', 120);
    }
    return [$list, $labels];
}

/** Shared settings: saved values over the defaults, always valid. */
function enw_layout_shared() {
    $d = enw_layout_default_shared();
    $s = enw_site_load();

    $nav = $d['nav'];
    if (isset($s['nav']) && is_array($s['nav'])) {
        [$nav] = enw_layout_clean_items($s['nav'], 'n_');
    }
    $fnav = [];
    if (isset($s['footer_nav']) && is_array($s['footer_nav'])) {
        [$fnav] = enw_layout_clean_items($s['footer_nav'], 'f_');
    }

    $brand = enw_layout_str($s['brand_name'] ?? $d['brand_name'], 60);
    $email = enw_layout_str($s['footer_email'] ?? $d['footer_email'], 200);
    $recipient = enw_layout_str($s['contact_recipient'] ?? $d['contact_recipient'], 200);

    return [
        'brand_name'       => $brand !== '' ? $brand : $d['brand_name'],
        'logo'             => array_key_exists('logo', $s) ? enw_clean_filename($s['logo']) : $d['logo'],
        'show_lang_switch' => array_key_exists('show_lang_switch', $s) ? !empty($s['show_lang_switch']) : $d['show_lang_switch'],
        'nav'              => $nav,
        'footer_same_nav'  => array_key_exists('footer_same_nav', $s) ? !empty($s['footer_same_nav']) : $d['footer_same_nav'],
        'footer_nav'       => $fnav,
        'footer_location'  => enw_layout_str($s['footer_location'] ?? $d['footer_location'], 200),
        'footer_email'     => ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : $d['footer_email'],
        'contact_recipient' => filter_var($recipient, FILTER_VALIDATE_EMAIL) ? $recipient : $d['contact_recipient'],
    ];
}

// --- translated texts ------------------------------------------------------

/**
 * The translated texts of one language, from its "layout" section, with the
 * old keys (nav.*, site.tagline) as the starting values when "layout" has
 * not been saved yet. A missing value is null; an empty string is a value.
 * $content = the language's whole content array.
 */
function enw_layout_bag(array $content) {
    $lay = (isset($content['layout']) && is_array($content['layout'])) ? $content['layout'] : [];
    $str = function ($v) { return is_string($v) ? $v : null; };

    $nav = [];
    foreach (enw_layout_legacy_nav_paths() as $id => $path) {
        $v = $str(enw_dig($content, $path));
        if ($v !== null) $nav[$id] = $v;
    }
    $fnav = [];
    if (isset($lay['nav']) && is_array($lay['nav'])) {
        foreach ($lay['nav'] as $id => $o) {
            if (is_array($o) && is_string($o['label'] ?? null)) $nav[$id] = $o['label'];
        }
    }
    if (isset($lay['footer_nav']) && is_array($lay['footer_nav'])) {
        foreach ($lay['footer_nav'] as $id => $o) {
            if (is_array($o) && is_string($o['label'] ?? null)) $fnav[$id] = $o['label'];
        }
    }

    return [
        'tagline'     => array_key_exists('tagline', $lay)     ? $str($lay['tagline'])     : $str(enw_dig($content, 'site.tagline')),
        'logo_alt'    => array_key_exists('logo_alt', $lay)    ? $str($lay['logo_alt'])    : $str(enw_dig($content, 'nav.logo_alt')),
        'toggle_aria' => array_key_exists('toggle_aria', $lay) ? $str($lay['toggle_aria']) : $str(enw_dig($content, 'nav.toggle_aria')),
        'copyright'   => array_key_exists('copyright', $lay)   ? $str($lay['copyright'])   : null,
        'nav'         => $nav,
        'footer_nav'  => $fnav,
    ];
}

/** Non-English: an empty or missing text uses the English one. */
function enw_layout_pick($v, $en, $isEn) {
    if (!$isEn && ($v === null || $v === '')) {
        $v = $en;
    }
    return $v === null ? '' : $v;
}

// --- resolve: shared + texts  =>  what the templates use -----------------

function enw_layout_build($lang, array $shared, array $bag, array $enBag) {
    $isEn = ($lang === 'en');
    $pick = function ($key) use ($bag, $enBag, $isEn) {
        return enw_layout_pick($bag[$key], $enBag[$key], $isEn);
    };
    $items = function (array $list, $bagKey) use ($bag, $enBag, $isEn) {
        $out = [];
        foreach ($list as $it) {
            $label = enw_layout_pick($bag[$bagKey][$it['id']] ?? null, $enBag[$bagKey][$it['id']] ?? null, $isEn);
            $out[] = $it + ['label' => $label];
        }
        return $out;
    };

    $copy = $pick('copyright');
    if ($copy === '' && $bag['copyright'] === null && $enBag['copyright'] === null) {
        $copy = ENW_DEFAULT_COPYRIGHT;
    }

    $nav = $items($shared['nav'], 'nav');
    return [
        'brand' => [
            'name'     => $shared['brand_name'],
            'logo'     => $shared['logo'],
            'logo_alt' => $pick('logo_alt'),
            'tagline'  => $pick('tagline'),
        ],
        'toggle_aria'      => $pick('toggle_aria'),
        'show_lang_switch' => $shared['show_lang_switch'],
        'nav'              => $nav,
        'footer' => [
            'same_nav'  => $shared['footer_same_nav'],
            'nav'       => $shared['footer_same_nav'] ? $nav : $items($shared['footer_nav'], 'footer_nav'),
            'location'  => $shared['footer_location'],
            'email'     => $shared['footer_email'],
            'copyright' => $copy,
        ],
        'contact_recipient' => $shared['contact_recipient'],
    ];
}

/** Layout for a language as saved on disk. */
function enw_layout_for($lang) {
    return enw_layout_build(
        $lang,
        enw_layout_shared(),
        enw_layout_bag(enw_lang_content($lang)),
        enw_layout_bag(enw_lang_content('en'))
    );
}

// --- editor (flat draft) <=> storage -----------------------------------------

/**
 * The flat shape the admin editor works with: shared settings and the texts
 * of ONE language side by side (no English fallback applied).
 */
function enw_layout_flat(array $shared, array $bag, $isEn) {
    $items = function (array $list, $bagKey) use ($bag) {
        $out = [];
        foreach ($list as $it) {
            $out[] = $it + ['label' => $bag[$bagKey][$it['id']] ?? ''];
        }
        return $out;
    };
    return [
        'brand_name'        => $shared['brand_name'],
        'logo'              => $shared['logo'],
        'show_lang_switch'  => $shared['show_lang_switch'],
        'footer_same_nav'   => $shared['footer_same_nav'],
        'footer_location'   => $shared['footer_location'],
        'footer_email'      => $shared['footer_email'],
        'contact_recipient' => $shared['contact_recipient'],
        'tagline'           => $bag['tagline'] ?? '',
        'logo_alt'          => $bag['logo_alt'] ?? '',
        'toggle_aria'       => $bag['toggle_aria'] ?? '',
        'copyright'         => $bag['copyright'] ?? ($isEn ? ENW_DEFAULT_COPYRIGHT : ''),
        'nav'               => $items($shared['nav'], 'nav'),
        'footer_nav'        => $items($shared['footer_nav'], 'footer_nav'),
    ];
}

/**
 * Clean the editor's draft and split it into [shared, texts, errors].
 * $texts is exactly what is stored under "layout" in {lang}.json.
 */
function enw_layout_split_draft(array $d) {
    $errors = [];
    $defaults = enw_layout_default_shared();

    [$nav, $navLabels]   = enw_layout_clean_items($d['nav'] ?? [], 'n_');
    [$fnav, $fnavLabels] = enw_layout_clean_items($d['footer_nav'] ?? [], 'f_');

    $brand = enw_layout_str($d['brand_name'] ?? '', 60);
    $email = enw_layout_str($d['footer_email'] ?? '', 200);
    $recipient = enw_layout_str($d['contact_recipient'] ?? '', 200);

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'The footer e-mail address is not valid.';
    }
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'The contact-form recipient must be a valid e-mail address (otherwise messages would be lost).';
    }
    foreach ($nav as $it) {
        if ($it['kind'] === 'url' && $it['url'] === '') {
            $errors[] = 'A custom navigation link has no valid address. Use https://…, /path, mailto:… or tel:…';
            break;
        }
    }

    $shared = [
        'brand_name'        => $brand !== '' ? $brand : $defaults['brand_name'],
        'logo'              => enw_clean_filename($d['logo'] ?? ''),
        'show_lang_switch'  => !empty($d['show_lang_switch']),
        'nav'               => $nav,
        'footer_same_nav'   => !empty($d['footer_same_nav']),
        'footer_nav'        => $fnav,
        'footer_location'   => enw_layout_str($d['footer_location'] ?? '', 200),
        'footer_email'      => $email,
        'contact_recipient' => $recipient,
    ];

    $asLabels = function (array $labels) {
        $o = [];
        foreach ($labels as $id => $label) $o[$id] = ['label' => $label];
        return $o;
    };
    $texts = [
        'tagline'     => enw_layout_str($d['tagline'] ?? '', 200),
        'logo_alt'    => enw_layout_str($d['logo_alt'] ?? '', 200),
        'toggle_aria' => enw_layout_str($d['toggle_aria'] ?? '', 200),
        'copyright'   => enw_layout_str($d['copyright'] ?? '', 300),
        'nav'         => $asLabels($navLabels),
        'footer_nav'  => $asLabels($fnavLabels),
    ];

    return [$shared, $texts, $errors];
}

/** Draft => the layout exactly as it would look if saved (for the live preview). */
function enw_layout_draft_to_layout(array $draft, $lang) {
    [$shared, $texts] = enw_layout_split_draft($draft);
    // Preview must never fail on a half-typed value: fall back to saved ones.
    if ($shared['contact_recipient'] === '' || !filter_var($shared['contact_recipient'], FILTER_VALIDATE_EMAIL)) {
        $shared['contact_recipient'] = enw_layout_shared()['contact_recipient'];
    }
    return enw_layout_build(
        $lang,
        $shared,
        enw_layout_bag(['layout' => $texts]),
        enw_layout_bag(enw_lang_content('en'))
    );
}
