<footer class="site-footer">
  <div class="wrap footer-inner">
    <span><?php echo SITE_LOCATION; ?></span>
    <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a>
    <nav class="footer-nav">
      <?php foreach ($NAV_ITEMS as $file => $label): ?>
        <?php $navHref = ($file === 'index.php') ? PAGE_BASE : PAGE_BASE . $file; ?>
        <a href="<?php echo $navHref; ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
  <div class="wrap footer-bottom">
    <span>ENWIRES &mdash; <?php echo SITE_YEAR; ?></span>
  </div>
</footer>

<script src="<?php echo ASSETS_URL; ?>assets/js/main.js"></script>
</body>
</html>
