<section class="band <?php echo enw_band($f['bg']); ?>"<?php echo $f['tight_bottom'] ? ' style="padding-bottom:0;"' : ''; ?><?php echo enw_block_attrs($b); ?>>
  <div class="wrap reveal">
    <div class="section-head">
      <h2><?php echo enw_rich($f['heading']); ?></h2>
      <?php if ($f['text'] !== ''): ?><p><?php echo enw_rich($f['text']); ?></p><?php endif; ?>
    </div>
  </div>
  <div class="filmstrip">
    <?php foreach ($f['images'] as $img): ?>
      <?php if ($img['src'] === '') continue; ?>
    <img src="<?php echo enw_img_src($img['src']); ?>" alt="<?php echo enw_attr($img['alt']); ?>" loading="lazy">
    <?php endforeach; ?>
  </div>
</section>
