<?php
// Footer content comes from the admin: /admin/layout.php
$L = enw_layout();
$F = $L['footer'];
?>
<footer class="site-footer">
  <div class="wrap footer-inner">
    <?php if ($F['location'] !== ''): ?>
    <span><?php echo htmlspecialchars($F['location']); ?></span>
    <?php endif; ?>
    <?php if ($F['email'] !== ''): ?>
    <a href="mailto:<?php echo enw_attr($F['email']); ?>"><?php echo htmlspecialchars($F['email']); ?></a>
    <?php endif; ?>
    <?php if ($F['nav']): ?>
    <nav class="footer-nav">
      <?php foreach ($F['nav'] as $item): ?>
        <a href="<?php echo enw_attr(enw_nav_href($item)); ?>"<?php echo enw_nav_target($item); ?>><?php echo enw_rich(enw_nav_label($item)); ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
  </div>
  <?php if ($F['copyright'] !== ''): ?>
  <div class="wrap footer-bottom">
    <span><?php echo str_replace('{year}', SITE_YEAR, enw_rich($F['copyright'])); ?></span>
  </div>
  <?php endif; ?>
</footer>

<script src="<?php echo ASSETS_URL; ?>assets/js/main.js"></script>
</body>
</html>
