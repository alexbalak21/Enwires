<?php
// One-time conversion of the old fixed-layout content (home.* / product.*
// keys in content/{lang}.json) into blocks.
//
//  - Reads your CURRENT content files, so nothing typed since the last
//    deploy is lost.
//  - Order, images and options come from the English file (they are shared
//    by all languages); the text of every language is carried over.
//  - The old keys are LEFT in place (harmless, ignored), and every language
//    file is backed up to data/backups/ before it is changed.
//  - Refuses to run if content/structure.json already exists.

require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/includes/blocks-store.php';

/** The blocks each page is made of, and where their content came from. */
function bm_plan() {
    return [
        'home' => [
            ['id' => 'home_hero', 'type' => 'hero',
             'text' => ['eyebrow' => 'home.hero.eyebrow', 'headline' => 'home.hero.headline', 'text' => 'home.hero.text',
                        'cta_primary' => 'home.hero.cta_primary', 'cta_secondary' => 'home.hero.cta_secondary'],
             'shared' => ['cta_primary_link' => 'product.php', 'cta_secondary_link' => 'contact.php']],

            ['id' => 'home_divider', 'type' => 'divider'],

            ['id' => 'home_who', 'type' => 'text-stat',
             'text' => ['heading' => 'home.who.heading', 'p1' => 'home.who.p1', 'p2' => 'home.who.p2',
                        'stat_number' => 'home.who.stat_number', 'stat_label' => 'home.who.stat_label'],
             'shared' => ['layout' => 'text-left', 'bg' => 'bg']],

            ['id' => 'home_offer', 'type' => 'card-list',
             'text' => ['heading' => 'home.offer.heading'],
             'repeaters' => ['items' => 'home.offer.items'],
             'shared' => ['bg' => 'charcoal']],

            ['id' => 'home_pilot', 'type' => 'image-strip',
             'text' => ['heading' => 'home.pilot.heading', 'text' => 'home.pilot.text'],
             'galleries' => ['images' => 'home.pilot.images'],
             'shared' => ['bg' => 'bg', 'tight_bottom' => false]],

            ['id' => 'home_filmstrip', 'type' => 'image-strip',
             'text' => ['heading' => 'home.filmstrip.heading', 'text' => 'home.filmstrip.text'],
             'galleries' => ['images' => 'home.filmstrip.images'],
             'shared' => ['bg' => 'charcoal', 'tight_bottom' => true]],
        ],

        'product' => [
            ['id' => 'product_intro', 'type' => 'page-intro',
             'text' => ['eyebrow' => 'product.hero.eyebrow', 'text' => 'product.hero.text'],
             'literal' => ['heading' => 'SiBoost']],

            ['id' => 'product_divider', 'type' => 'divider'],

            ['id' => 'product_what', 'type' => 'text-stat',
             'text' => ['heading' => 'product.what.heading', 'p1' => 'product.what.p1', 'p2' => 'product.what.p2',
                        'stat_number' => 'product.stat.number', 'stat_label' => 'product.stat.label'],
             'shared' => ['layout' => 'stat-left', 'bg' => 'paper']],

            ['id' => 'product_compare', 'type' => 'figure',
             'text' => ['heading' => 'product.compare.heading', 'text' => 'product.compare.text'],
             'images' => ['image' => 'product.compare.image'],
             'shared' => ['bg' => 'bg']],

            ['id' => 'product_how', 'type' => 'steps',
             'text' => ['heading' => 'product.how.heading'],
             'repeaters' => ['steps' => 'product.how.steps'],
             'galleries' => ['images' => 'product.how.images'],
             'shared' => ['bg' => 'charcoal']],

            ['id' => 'product_cells', 'type' => 'image-strip',
             'text' => ['heading' => 'product.cells.heading', 'text' => 'product.cells.text'],
             'galleries' => ['images' => 'product.cells.images'],
             'shared' => ['bg' => 'bg', 'tight_bottom' => false]],

            ['id' => 'product_cta', 'type' => 'cta',
             'text' => ['heading' => 'product.cta.heading', 'text' => 'product.cta.text', 'button' => 'product.cta.button'],
             'shared' => ['button_link' => 'contact.php', 'bg' => 'charcoal']],
        ],
    ];
}

function bm_list($data, $path) {
    $v = cs_get($data, $path, []);
    return is_array($v) ? array_values($v) : [];
}

/** Runs the conversion. Returns [bool ok, array of report lines]. */
function bm_run() {
    $report = [];
    if (enw_structure_exists()) {
        return [false, ['content/structure.json already exists — nothing to do. (Delete it only if you really want to convert again from the old keys.)']];
    }

    $types = enw_block_types();
    $plan = bm_plan();
    $content = [];
    foreach (BS_LANGS as $l) {
        $content[$l] = cs_load($l);
    }
    $en = $content['en'];

    $structure = [];
    $texts = ['en' => [], 'fr' => [], 'zh' => [], 'ja' => []];

    foreach ($plan as $page => $blocks) {
        $structure[$page] = [];

        foreach ($blocks as $def) {
            $id = $def['id'];
            $type = $def['type'];
            $shared = $def['shared'] ?? [];

            // Scalar texts, per language (non-English: empty => fall back to English).
            foreach (BS_LANGS as $l) {
                $t = [];
                foreach (($def['text'] ?? []) as $name => $path) {
                    $v = cs_get($content[$l], $path, null);
                    if (is_string($v) && ($v !== '' || $l === 'en')) $t[$name] = $v;
                }
                foreach (($def['literal'] ?? []) as $name => $v) {
                    $t[$name] = $v;
                }
                $texts[$l][$id] = $t;
            }

            // Single images: src from English; alt / caption per language.
            foreach (($def['images'] ?? []) as $name => $path) {
                $img = cs_get($en, $path, []);
                $shared[$name] = ['src' => enw_clean_filename($img['src'] ?? '')];
                foreach (BS_LANGS as $l) {
                    $li = cs_get($content[$l], $path, []);
                    $ti = [];
                    foreach (['alt', 'caption'] as $k) {
                        if (isset($li[$k]) && is_string($li[$k]) && ($li[$k] !== '' || $l === 'en')) $ti[$k] = $li[$k];
                    }
                    if ($ti) $texts[$l][$id][$name] = $ti;
                }
            }

            // Galleries: order + src from English; alt per language, matched by position.
            foreach (($def['galleries'] ?? []) as $name => $path) {
                $enItems = bm_list($en, $path);
                $sl = [];
                foreach ($enItems as $i => $it) {
                    $sl[] = ['id' => 'i' . ($i + 1), 'src' => enw_clean_filename($it['src'] ?? '')];
                }
                $shared[$name] = $sl;
                foreach (BS_LANGS as $l) {
                    $items = bm_list($content[$l], $path);
                    if (count($items) !== count($enItems)) {
                        $report[] = "Note: $path has " . count($items) . " item(s) in $l but " . count($enItems) . ' in en — matched by position.';
                    }
                    $tl = [];
                    foreach ($enItems as $i => $_) {
                        $alt = isset($items[$i]['alt']) && is_string($items[$i]['alt']) ? $items[$i]['alt'] : '';
                        if ($alt !== '' || $l === 'en') $tl['i' . ($i + 1)] = ['alt' => $alt];
                    }
                    if ($tl) $texts[$l][$id][$name] = $tl;
                }
            }

            // Repeaters: order from English; sub-field texts per language, matched by position.
            foreach (($def['repeaters'] ?? []) as $name => $path) {
                $fieldDef = null;
                foreach ($types[$type]['fields'] as $fd) {
                    if ($fd['name'] === $name) $fieldDef = $fd;
                }
                $enItems = bm_list($en, $path);
                $sl = [];
                foreach ($enItems as $i => $_) {
                    $sl[] = ['id' => 'i' . ($i + 1)];
                }
                $shared[$name] = $sl;
                foreach (BS_LANGS as $l) {
                    $items = bm_list($content[$l], $path);
                    if (count($items) !== count($enItems)) {
                        $report[] = "Note: $path has " . count($items) . " item(s) in $l but " . count($enItems) . ' in en — matched by position.';
                    }
                    $tl = [];
                    foreach ($enItems as $i => $_) {
                        $row = [];
                        foreach ($fieldDef['item_fields'] as $sub) {
                            $v = isset($items[$i][$sub['name']]) && is_string($items[$i][$sub['name']]) ? $items[$i][$sub['name']] : '';
                            if ($v !== '' || $l === 'en') $row[$sub['name']] = $v;
                        }
                        if ($row) $tl['i' . ($i + 1)] = $row;
                    }
                    if ($tl) $texts[$l][$id][$name] = $tl;
                }
            }

            $structure[$page][] = ['id' => $id, 'type' => $type, 'shared' => $shared];
        }
    }

    // Write the language files first (each one is backed up by cs_save), then the structure.
    foreach (BS_LANGS as $l) {
        $data = $content[$l];
        $existing = (isset($data['blocks']) && is_array($data['blocks'])) ? $data['blocks'] : [];
        foreach ($texts[$l] as $id => $t) {
            $existing[$id] = $t;
        }
        $data['blocks'] = $existing;
        if (!cs_save($l, $data)) {
            return [false, array_merge($report, ["Could not write content/$l.json — make the content folder writable and try again. Nothing was converted."])];
        }
        $report[] = "content/$l.json: text of " . count($texts[$l]) . ' blocks added (original backed up to data/backups/).';
    }

    if (!bs_structure_save($structure)) {
        return [false, array_merge($report, ['Could not write content/structure.json — make the content folder writable and run this again.'])];
    }
    $report[] = 'content/structure.json created: ' . count($structure['home']) . ' blocks on Home, ' . count($structure['product']) . ' on Product.';

    return [true, $report];
}

$done = null;
$report = [];
$already = enw_structure_exists();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf()) {
        $report = ['Your session expired — please try again.'];
        $done = false;
    } else {
        [$done, $report] = bm_run();
        $already = enw_structure_exists();
    }
}

$pageTitle = 'Convert to blocks';
require __DIR__ . '/includes/layout-header.php';
?>

<a href="index.php" class="back-link">&larr; Back to dashboard</a>
<h1>Convert the site to blocks</h1>
<p class="subtitle">A one-time step that turns the current Home and Product pages into editable blocks.</p>

<?php if ($done === true): ?>
  <div class="alert alert-success">Done. Your pages are now made of blocks.</div>
<?php elseif ($done === false): ?>
  <div class="alert alert-error">The conversion did not complete.</div>
<?php endif; ?>

<?php if ($report): ?>
  <div class="panel">
    <ul style="margin:0; padding-left:20px;">
      <?php foreach ($report as $line): ?><li><?php echo htmlspecialchars($line); ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if ($already): ?>
  <div class="panel">
    <p style="margin-top:0;">The site is already using blocks.</p>
    <a class="btn-primary" style="display:inline-block; text-decoration:none;" href="builder.php?page=home&amp;lang=en">Open the Home builder</a>
    &nbsp;
    <a class="btn-secondary" style="display:inline-block; text-decoration:none;" href="builder.php?page=product&amp;lang=en">Open the Product builder</a>
  </div>
<?php else: ?>
  <div class="panel">
    <p style="margin-top:0;">This will:</p>
    <ul>
      <li>read your current content in all four languages,</li>
      <li>create <code>content/structure.json</code> (order, images and options shared by every language),</li>
      <li>add each language's text under a <code>blocks</code> key in its JSON file,</li>
      <li>back up every file to <code>data/backups/</code> first, and leave the old keys untouched.</li>
    </ul>
    <p>The public pages show nothing until this has been run, so do it right after uploading the new files.</p>
    <form method="post">
      <?php echo admin_csrf_field(); ?>
      <button type="submit" class="btn-primary">Convert now</button>
    </form>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
