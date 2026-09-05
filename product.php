<?php
$pageTitle = 'SiBoost';
$pageDescription = 'SiBoost is a silicon-doped graphite composite that delivers 1.1 to 4 times the energy density of standard graphite in lithium-ion batteries.';
require_once __DIR__ . '/includes/header.php';
?>

<section class="band band-charcoal" style="padding-top:64px;">
  <div class="wrap">
    <p class="hero-eyebrow">Product</p>
    <h1 style="font-size:clamp(2rem,4.5vw,3.2rem); max-width:16ch;">SiBoost</h1>
    <p style="font-size:1.1rem; color:var(--ink-dim); max-width:56ch;">
      A silicon-doped graphite designed to increase the energy density of
      lithium-ion battery anodes, built to run on existing production lines.
    </p>
  </div>
</section>

<div class="ribbon-divider" aria-hidden="true"></div>

<section class="band band-paper">
  <div class="wrap split">
    <div class="stat">
      <div class="num">1.1&ndash;4&times;</div>
      <div class="label">energy density compared with the graphite currently
        used in lithium-ion batteries</div>
    </div>
    <div>
      <h2 style="font-size:1.5rem;">What is SiBoost?</h2>
      <p>SiBoost is a graphite-silicon composite (Gr-Si) that pairs graphite's
        electronic conductivity with silicon's ability to interact with and
        store lithium ions.</p>
      <p>That combination is what pushes the energy density beyond what
        graphite alone can offer &mdash; anywhere from 1.1 to 4 times the
        performance, depending on the formulation and the target application.</p>
    </div>
  </div>
</section>

<section class="band band-bg">
  <div class="wrap">
    <div class="section-head">
      <h2>Before and after</h2>
      <p>The same base graphite, before treatment and after silicon doping.</p>
    </div>
    <div class="compare">
      <figure>
        <img src="assets/img/bottles.jpg" alt="Two glass bottles side by side: raw graphite powder on the left, darker SiBoost silicon-doped powder on the right, both labelled Enwires">
        <figcaption>Left: standard graphite. Right: SiBoost, silicon-doped graphite.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="band band-charcoal">
  <div class="wrap split">
    <div>
      <h2 style="font-size:1.5rem;">How it works</h2>
      <ul class="steps">
        <li><strong>Start from standard graphite.</strong> Most grades used in the
          industry today, including material that's currently under-used.</li>
        <li><strong>Introduce silicon at the particle level.</strong> Silicon is
          bonded into the graphite structure rather than simply blended in.</li>
        <li><strong>Verify under electron microscopy.</strong> Every batch is
          checked at the particle surface before it ships.</li>
      </ul>
    </div>
    <div>
      <img src="assets/img/graphite-particle.jpg" alt="SEM image of a single SiBoost particle" style="border:1px solid var(--line);">
    </div>
  </div>
</section>

<section class="band band-bg">
  <div class="wrap" style="text-align:left;">
    <h2 style="font-size:1.6rem;">Bring your own graphite</h2>
    <p style="color:var(--ink-dim);">Our production line adapts to most graphite
      materials already used in the industry &mdash; tell us what you're working
      with and we'll tell you what SiBoost can do with it.</p>
    <a href="contact.php" class="btn btn-primary">Get in touch</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
