<?php $imgs = array_values(array_filter($f['images'], fn($i) => $i['src'] !== '')); $imgCount = count($imgs); ?>
<section class="band <?php echo enw_band($f['bg']); ?>"<?php echo enw_block_attrs($b); ?>>
  <div class="wrap split reveal">
    <div>
      <h2 style="font-size:1.5rem;"><?php echo enw_rich($f['heading']); ?></h2>
      <ul class="steps">
        <?php foreach ($f['steps'] as $step): ?>
        <li><strong><?php echo enw_rich($step['title']); ?></strong> <?php echo enw_rich($step['text']); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <?php foreach ($imgs as $i => $img): ?>
      <img src="<?php echo enw_img_src($img['src']); ?>" alt="<?php echo enw_attr($img['alt']); ?>" style="border:1px solid var(--line); <?php echo ($i < $imgCount - 1) ? 'margin-bottom:12px;' : ''; ?>">
      <?php endforeach; ?>
    </div>
  </div>
</section>
