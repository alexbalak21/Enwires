<?php
$isRootEntry = !isset($lang); // true only when visited directly as "/" or "/index.php"
if (!isset($lang)) { $lang = 'en'; }
require_once __DIR__ . '/includes/config.php';

if ($isRootEntry) {
    require_once __DIR__ . '/includes/language-detect.php'; // may redirect + exit
}

$pageTitle = t('home.meta.title');
$pageDescription = t('home.meta.description');
$pageKeywords = t('home.meta.keywords');
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="wrap hero-inner">
    <p class="hero-eyebrow"><?php echo t('home.hero.eyebrow'); ?></p>
    <h1><?php echo t('home.hero.headline'); ?></h1>
    <p><?php echo t('home.hero.text'); ?></p>
    <div class="hero-cta">
      <a href="<?php echo PAGE_BASE; ?>product.php" class="btn btn-primary"><?php echo t('home.hero.cta_primary'); ?></a>
      <a href="<?php echo PAGE_BASE; ?>contact.php" class="btn btn-ghost"><?php echo t('home.hero.cta_secondary'); ?></a>
    </div>
  </div>
</section>

<div class="ribbon-divider" aria-hidden="true"></div>

<section class="band band-bg">
  <div class="wrap split reveal">
    <div>
      <div class="section-head">
        <h2><?php echo t('home.who.heading'); ?></h2>
      </div>
      <p><?php echo t('home.who.p1'); ?></p>
      <p><?php echo t('home.who.p2'); ?></p>
    </div>
    <div class="stat">
      <div class="num"><?php echo t('home.who.stat_number'); ?></div>
      <div class="label"><?php echo t('home.who.stat_label'); ?></div>
    </div>
  </div>
</section>

<section class="band band-charcoal">
  <div class="wrap reveal">
    <div class="section-head">
      <h2><?php echo t('home.offer.heading'); ?></h2>
    </div>
    <ul class="value-list">
      <?php foreach (t_list('home.offer.items') as $item): ?>
      <li>
        <h3><?php echo htmlspecialchars($item['title'] ?? ''); ?></h3>
        <p><?php echo htmlspecialchars($item['text'] ?? ''); ?></p>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="band band-bg">
  <div class="wrap reveal">
    <div class="section-head">
      <h2><?php echo t('home.pilot.heading'); ?></h2>
      <p><?php echo t('home.pilot.text'); ?></p>
    </div>
  </div>
  <div class="filmstrip">
    <?php foreach (t_list('home.pilot.images') as $img): ?>
    <img src="<?php echo ASSETS_URL . 'assets/img/' . htmlspecialchars($img['src'] ?? ''); ?>" alt="<?php echo htmlspecialchars($img['alt'] ?? ''); ?>" loading="lazy">
    <?php endforeach; ?>
  </div>
</section>

<section class="band band-charcoal" style="padding-bottom:0;">
  <div class="wrap reveal">
    <div class="section-head">
      <h2><?php echo t('home.filmstrip.heading'); ?></h2>
      <p><?php echo t('home.filmstrip.text'); ?></p>
    </div>
  </div>
  <div class="filmstrip">
    <?php foreach (t_list('home.filmstrip.images') as $img): ?>
    <img src="<?php echo ASSETS_URL . 'assets/img/' . htmlspecialchars($img['src'] ?? ''); ?>" alt="<?php echo htmlspecialchars($img['alt'] ?? ''); ?>" loading="lazy">
    <?php endforeach; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
