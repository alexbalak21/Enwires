<section class="band <?php echo enw_band($f['bg']); ?>"<?php echo enw_block_attrs($b); ?>>
  <div class="wrap reveal" style="text-align:left;">
    <h2 style="font-size:1.6rem;"><?php echo enw_rich($f['heading']); ?></h2>
    <?php if ($f['text'] !== ''): ?><p style="color:var(--ink-dim);"><?php echo enw_rich($f['text']); ?></p><?php endif; ?>
    <?php if ($f['button'] !== ''): ?>
    <a href="<?php echo enw_attr(enw_link_href($f['button_link'])); ?>" class="btn btn-primary"><?php echo enw_rich($f['button']); ?></a>
    <?php endif; ?>
  </div>
</section>
