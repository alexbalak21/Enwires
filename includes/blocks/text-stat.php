<?php $statLeft = ($f['layout'] === 'stat-left'); ?>
<section class="band <?php echo enw_band($f['bg']); ?>"<?php echo enw_block_attrs($b); ?>>
  <div class="wrap split reveal">
    <?php if ($statLeft): ?>
    <div class="stat">
      <div class="num"><?php echo enw_rich($f['stat_number']); ?></div>
      <div class="label"><?php echo enw_rich($f['stat_label']); ?></div>
    </div>
    <div>
      <h2 style="font-size:1.5rem;"><?php echo enw_rich($f['heading']); ?></h2>
      <?php if ($f['p1'] !== ''): ?><p><?php echo enw_rich($f['p1']); ?></p><?php endif; ?>
      <?php if ($f['p2'] !== ''): ?><p><?php echo enw_rich($f['p2']); ?></p><?php endif; ?>
    </div>
    <?php else: ?>
    <div>
      <div class="section-head">
        <h2><?php echo enw_rich($f['heading']); ?></h2>
      </div>
      <?php if ($f['p1'] !== ''): ?><p><?php echo enw_rich($f['p1']); ?></p><?php endif; ?>
      <?php if ($f['p2'] !== ''): ?><p><?php echo enw_rich($f['p2']); ?></p><?php endif; ?>
    </div>
    <div class="stat">
      <div class="num"><?php echo enw_rich($f['stat_number']); ?></div>
      <div class="label"><?php echo enw_rich($f['stat_label']); ?></div>
    </div>
    <?php endif; ?>
  </div>
</section>
