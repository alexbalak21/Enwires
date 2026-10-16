<section class="hero"<?php echo enw_block_attrs($b); ?>>
  <div class="wrap hero-inner">
    <?php if ($f['eyebrow'] !== ''): ?><p class="hero-eyebrow"><?php echo enw_rich($f['eyebrow']); ?></p><?php endif; ?>
    <h1><?php echo enw_rich($f['headline']); ?></h1>
    <?php if ($f['text'] !== ''): ?><p><?php echo enw_rich($f['text']); ?></p><?php endif; ?>
    <?php if ($f['cta_primary'] !== '' || $f['cta_secondary'] !== ''): ?>
    <div class="hero-cta">
      <?php if ($f['cta_primary'] !== ''): ?>
      <a href="<?php echo enw_attr(enw_link_href($f['cta_primary_link'])); ?>" class="btn btn-primary"><?php echo enw_rich($f['cta_primary']); ?></a>
      <?php endif; ?>
      <?php if ($f['cta_secondary'] !== ''): ?>
      <a href="<?php echo enw_attr(enw_link_href($f['cta_secondary_link'])); ?>" class="btn btn-ghost"><?php echo enw_rich($f['cta_secondary']); ?></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
