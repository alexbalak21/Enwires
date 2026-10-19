<?php
// Background. The stylesheet draws the default (microscope photo under a dark gradient).
// The options of the block override it with an inline style; nothing is added when the
// defaults are kept, so the look of the site does not change until you choose something.
$overlays = ['none' => [0, 0], 'soft' => [0.15, 0.75], 'normal' => [0.35, 0.92], 'strong' => [0.6, 0.96]];
list($a1, $a2) = isset($overlays[$f['bg_overlay']]) ? $overlays[$f['bg_overlay']] : $overlays['normal'];
$gradient = ($a1 > 0 || $a2 > 0)
    ? 'linear-gradient(180deg, rgba(43, 43, 44, ' . $a1 . ') 0%, rgba(30, 30, 31, ' . $a2 . ') 78%)'
    : '';
$heroStyle = '';
if ($f['bg_style'] === 'color') {
    $heroStyle = 'background-color:' . $f['bg_color'] . '; background-image:none;';
} elseif ($f['bg_style'] === 'image' && $f['bg_image']['src'] !== '') {
    $heroStyle = 'background-image:' . ($gradient !== '' ? $gradient . ', ' : '')
               . "url('" . ASSETS_URL . 'assets/img/' . $f['bg_image']['src'] . "');";
} elseif ($f['bg_overlay'] !== 'normal') {
    // The default photo with a different darkening. (This is the photo named in style.css.)
    $heroStyle = 'background-image:' . ($gradient !== '' ? $gradient . ', ' : '')
               . "url('" . ASSETS_URL . "assets/img/electronic-microscope-molecule-zoom.jpg');";
}
if ($f['bg_tone'] === 'dark') {
    // Re-point the two text colors the stylesheet uses; every element inside follows.
    $heroStyle .= ' --ink:var(--ink-on-paper); --ink-dim:var(--ink-on-paper-dim);';
}
?>
<section class="hero"<?php echo $heroStyle !== '' ? ' style="' . enw_attr(trim($heroStyle)) . '"' : ''; ?><?php echo enw_block_attrs($b); ?>>
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
