<?php
if (!isset($lang)) { $lang = 'en'; }
require_once __DIR__ . '/includes/config.php';

$pageTitle = t('product.meta.title');
$pageDescription = t('product.meta.description');
$pageKeywords = t('product.meta.keywords');
$pageImage = 'assets/img/bottles.jpg';
require_once __DIR__ . '/includes/header.php';
?>

<section class="band band-charcoal" style="padding-top:64px;">
  <div class="wrap">
    <p class="hero-eyebrow"><?php echo t('product.hero.eyebrow'); ?></p>
    <h1 style="font-size:clamp(2rem,4.5vw,3.2rem); max-width:16ch;">SiBoost</h1>
    <p style="font-size:1.1rem; color:var(--ink-dim); max-width:56ch;">
      <?php echo t('product.hero.text'); ?>
    </p>
  </div>
</section>

<div class="ribbon-divider" aria-hidden="true"></div>

<section class="band band-paper">
  <div class="wrap split reveal">
    <div class="stat">
      <div class="num"><?php echo t('product.stat.number'); ?></div>
      <div class="label"><?php echo t('product.stat.label'); ?></div>
    </div>
    <div>
      <h2 style="font-size:1.5rem;"><?php echo t('product.what.heading'); ?></h2>
      <p><?php echo t('product.what.p1'); ?></p>
      <p><?php echo t('product.what.p2'); ?></p>
    </div>
  </div>
</section>

<section class="band band-bg">
  <div class="wrap reveal">
    <div class="section-head">
      <h2><?php echo t('product.compare.heading'); ?></h2>
      <p><?php echo t('product.compare.text'); ?></p>
    </div>
    <div class="compare">
      <figure>
        <img src="<?php echo ASSETS_URL; ?>assets/img/bottles.jpg" alt="<?php echo htmlspecialchars(t('product.compare.alt')); ?>">
        <figcaption><?php echo t('product.compare.caption'); ?></figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="band band-charcoal">
  <div class="wrap split reveal">
    <div>
      <h2 style="font-size:1.5rem;"><?php echo t('product.how.heading'); ?></h2>
      <ul class="steps">
        <li><strong><?php echo t('product.how.step1.title'); ?></strong> <?php echo t('product.how.step1.text'); ?></li>
        <li><strong><?php echo t('product.how.step2.title'); ?></strong> <?php echo t('product.how.step2.text'); ?></li>
        <li><strong><?php echo t('product.how.step3.title'); ?></strong> <?php echo t('product.how.step3.text'); ?></li>
      </ul>
    </div>
    <div>
      <img src="<?php echo ASSETS_URL; ?>assets/img/graphite-particle.jpg" alt="<?php echo htmlspecialchars(t('product.how.image_alt')); ?>" style="border:1px solid var(--line);">
    </div>
  </div>
</section>

<section class="band band-bg">
  <div class="wrap reveal" style="text-align:left;">
    <h2 style="font-size:1.6rem;"><?php echo t('product.cta.heading'); ?></h2>
    <p style="color:var(--ink-dim);"><?php echo t('product.cta.text'); ?></p>
    <a href="<?php echo PAGE_BASE; ?>contact.php" class="btn btn-primary"><?php echo t('product.cta.button'); ?></a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
