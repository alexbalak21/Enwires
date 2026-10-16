<section class="band <?php echo enw_band($f['bg']); ?>"<?php echo enw_block_attrs($b); ?>>
  <div class="wrap reveal">
    <div class="section-head">
      <h2><?php echo enw_rich($f['heading']); ?></h2>
    </div>
    <ul class="value-list">
      <?php foreach ($f['items'] as $item): ?>
      <li>
        <h3><?php echo enw_rich($item['title']); ?></h3>
        <p><?php echo enw_rich($item['text']); ?></p>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
