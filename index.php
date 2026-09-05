<?php
$pageTitle = 'Home';
$pageDescription = 'Enwires is a Grenoble-based materials company developing SiBoost, a silicon-graphite composite that increases the energy density of lithium-ion batteries.';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="wrap hero-inner">
    <p class="hero-eyebrow">Battery materials, engineered in Grenoble</p>
    <h1>More energy in your battery.</h1>
    <p>Enwires builds silicon-graphite composites that raise the energy density of
      lithium-ion batteries &mdash; without changing how they're manufactured.</p>
    <div class="hero-cta">
      <a href="product.php" class="btn btn-primary">See SiBoost</a>
      <a href="contact.php" class="btn btn-ghost">Talk to us</a>
    </div>
  </div>
</section>

<div class="ribbon-divider" aria-hidden="true"></div>

<section class="band band-bg">
  <div class="wrap split">
    <div>
      <div class="section-head">
        <h2>Who we are</h2>
      </div>
      <p>Enwires is a team of around ten people, working alongside experts from
        the battery industry. We focus on one problem: how far a graphite-based
        anode can be pushed before it needs to be replaced.</p>
      <p>Lithium-ion batteries are one of the most widely used technologies of
        modern life, and one of the most constrained. We build materials that
        extend what's already in production, rather than asking manufacturers to
        start over.</p>
    </div>
    <div class="stat">
      <div class="num">1.1&ndash;4&times;</div>
      <div class="label">the energy density of standard graphite, from our
        SiBoost composite</div>
    </div>
  </div>
</section>

<section class="band band-charcoal">
  <div class="wrap">
    <div class="section-head">
      <h2>What we offer</h2>
    </div>
    <ul class="value-list">
      <li>
        <h3>Higher energy density</h3>
        <p>SiBoost combines graphite's conductivity with silicon's capacity to
          store lithium, so cells can hold more energy at the same size.</p>
      </li>
      <li>
        <h3>Drop-in compatibility</h3>
        <p>Our process adapts to graphite grades already used across the industry,
          including material that's currently under-used because of its
          characteristics.</p>
      </li>
      <li>
        <h3>Smaller or lighter cells</h3>
        <p>The same performance in less volume and weight &mdash; or more
          performance in the footprint manufacturers already have.</p>
      </li>
    </ul>
  </div>
</section>

<section class="band band-bg" style="padding-bottom:0;">
  <div class="wrap">
    <div class="section-head">
      <h2>From powder to particle</h2>
      <p>Every batch is verified under electron microscopy before it leaves the lab.</p>
    </div>
  </div>
  <div class="filmstrip">
    <img src="assets/img/graphite-pouder.jpg" alt="Raw graphite powder, before treatment" loading="lazy">
    <img src="assets/img/graphite-pouder-purifued.jpg" alt="Purified, silicon-doped graphite powder" loading="lazy">
    <img src="assets/img/graphite-particle.jpg" alt="Single graphite particle under electron microscope" loading="lazy">
    <img src="assets/img/micorscorpe-zoom.jpg" alt="Particle surface texture under microscope" loading="lazy">
    <img src="assets/img/microscope-zoom-2.jpg" alt="Close-up of coated particle surface" loading="lazy">
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
