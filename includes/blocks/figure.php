<section class="band <?php echo enw_band($f['bg']); ?>"<?php echo enw_block_attrs($b); ?>>
  <div class="wrap reveal">
    <div class="section-head">
      <h2><?php echo enw_rich($f['heading']); ?></h2>
      <?php if ($f['text'] !== ''): ?><p><?php echo enw_rich($f['text']); ?></p><?php endif; ?>
    </div>
    <?php if ($f['image']['src'] !== ''): ?>
    <div class="compare">
      <figure>
        <img src="<?php echo enw_img_src($f['image']['src']); ?>" alt="<?php echo enw_attr($f['image']['alt']); ?>">
        <?php if ($f['image']['caption'] !== ''): ?><figcaption><?php echo enw_rich($f['image']['caption']); ?></figcaption><?php endif; ?>
      </figure>
    </div>
    <?php endif; ?>
  </div>
</section>
