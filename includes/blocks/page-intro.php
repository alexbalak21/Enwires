<section class="band band-charcoal" style="padding-top:64px;"<?php echo enw_block_attrs($b); ?>>
  <div class="wrap">
    <?php if ($f['eyebrow'] !== ''): ?><p class="hero-eyebrow"><?php echo enw_rich($f['eyebrow']); ?></p><?php endif; ?>
    <h1 style="font-size:clamp(2rem,4.5vw,3.2rem); max-width:16ch;"><?php echo enw_rich($f['heading']); ?></h1>
    <?php if ($f['text'] !== ''): ?>
    <p style="font-size:1.1rem; color:var(--ink-dim); max-width:56ch;">
      <?php echo enw_rich($f['text']); ?>
    </p>
    <?php endif; ?>
  </div>
</section>
