<?php
require_once __DIR__ . '/config.php';
/** @var array<string,string> $NAV_ITEMS */
?>
<footer class="site-footer">
  <div class="wrap footer-inner">
    <span><?php echo SITE_LOCATION; ?></span>
    <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a>
    <nav class="footer-nav">
      <?php foreach ($NAV_ITEMS as $href => $label): ?>
        <a href="<?php echo nav_href($href); ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
  <div class="wrap footer-bottom">
    <span>ENWIRES &mdash; <?php echo SITE_YEAR; ?></span>
  </div>
</footer>

<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>