<?php
if (!isset($lang)) { $lang = 'en'; }
require_once __DIR__ . '/includes/config.php';

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
      <li>
        <h3><?php echo t('home.offer.item1.title'); ?></h3>
        <p><?php echo t('home.offer.item1.text'); ?></p>
      </li>
      <li>
        <h3><?php echo t('home.offer.item2.title'); ?></h3>
        <p><?php echo t('home.offer.item2.text'); ?></p>
      </li>
      <li>
        <h3><?php echo t('home.offer.item3.title'); ?></h3>
        <p><?php echo t('home.offer.item3.text'); ?></p>
      </li>
    </ul>
  </div>
</section>

<section class="band band-bg" style="padding-bottom:0;">
  <div class="wrap reveal">
    <div class="section-head">
      <h2><?php echo t('home.filmstrip.heading'); ?></h2>
      <p><?php echo t('home.filmstrip.text'); ?></p>
    </div>
  </div>
  <div class="filmstrip">
    <img src="<?php echo ASSETS_URL; ?>assets/img/graphite-pouder.jpg" alt="<?php echo htmlspecialchars(t('home.filmstrip.alt1')); ?>" loading="lazy">
    <img src="<?php echo ASSETS_URL; ?>assets/img/graphite-pouder-purifued.jpg" alt="<?php echo htmlspecialchars(t('home.filmstrip.alt2')); ?>" loading="lazy">
    <img src="<?php echo ASSETS_URL; ?>assets/img/graphite-particle.jpg" alt="<?php echo htmlspecialchars(t('home.filmstrip.alt3')); ?>" loading="lazy">
    <img src="<?php echo ASSETS_URL; ?>assets/img/micorscorpe-zoom.jpg" alt="<?php echo htmlspecialchars(t('home.filmstrip.alt4')); ?>" loading="lazy">
    <img src="<?php echo ASSETS_URL; ?>assets/img/microscope-zoom-2.jpg" alt="<?php echo htmlspecialchars(t('home.filmstrip.alt5')); ?>" loading="lazy">
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
