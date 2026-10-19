<?php
// The contact form and the details beside it. The form's LOGIC (validation, sending)
// is in contact.php, which hands its state over in $GLOBALS['enw_contact'];
// this template only draws it. All texts come from the block (edited in the builder).
$cs = (isset($GLOBALS['enw_contact']) && is_array($GLOBALS['enw_contact'])) ? $GLOBALS['enw_contact'] : [];
$cSubmitted = !empty($cs['submitted']);
$cErrors = (isset($cs['errors']) && is_array($cs['errors'])) ? $cs['errors'] : [];
$cValues = array_merge(
    ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''],
    (isset($cs['values']) && is_array($cs['values'])) ? $cs['values'] : []
);
$cLayout = enw_layout();
$cEmail = $cLayout['footer']['email'] !== '' ? $cLayout['footer']['email'] : $cLayout['contact_recipient'];
$cLocation = $cLayout['footer']['location'];
?>
<section class="band <?php echo enw_band($f['bg']); ?>"<?php echo enw_block_attrs($b); ?>>
  <div class="wrap split reveal">
    <div>
      <?php if ($cSubmitted): ?>
        <div class="alert alert-success"><?php echo enw_rich($f['success']); ?></div>
      <?php elseif ($cErrors): ?>
        <div class="alert alert-error"><?php echo enw_rich(implode(' ', $cErrors)); ?></div>
      <?php endif; ?>

      <form action="<?php echo enw_attr(PAGE_BASE . 'contact'); ?>" method="post" novalidate>
        <div style="position:absolute; left:-9999px;" aria-hidden="true">
          <label for="website">Leave this field empty</label>
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>
        <div class="form-grid">
          <div class="field">
            <label for="name"><?php echo enw_rich($f['name_label']); ?></label>
            <input type="text" id="name" name="name" required value="<?php echo enw_attr($cValues['name']); ?>">
          </div>
          <div class="field">
            <label for="phone"><?php echo enw_rich($f['phone_label']); ?></label>
            <input type="tel" id="phone" name="phone" value="<?php echo enw_attr($cValues['phone']); ?>">
          </div>
          <div class="field full">
            <label for="email"><?php echo enw_rich($f['email_label']); ?></label>
            <input type="email" id="email" name="email" required value="<?php echo enw_attr($cValues['email']); ?>">
          </div>
          <div class="field full">
            <label for="subject"><?php echo enw_rich($f['subject_label']); ?></label>
            <input type="text" id="subject" name="subject" value="<?php echo enw_attr($cValues['subject']); ?>">
          </div>
          <div class="field full">
            <label for="message"><?php echo enw_rich($f['message_label']); ?></label>
            <textarea id="message" name="message" required><?php echo htmlspecialchars($cValues['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
          </div>
        </div>
        <p class="form-note"><?php echo enw_rich($f['required_note']); ?></p>
        <button type="submit" class="btn btn-primary"><?php echo enw_rich($f['submit']); ?></button>
      </form>
    </div>

    <?php if (!empty($f['show_info'])): ?>
    <div class="contact-info">
      <dl>
        <?php if ($cLocation !== ''): ?>
        <dt><?php echo enw_rich($f['info_location_label']); ?></dt>
        <dd><?php echo htmlspecialchars($cLocation, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd>
        <?php endif; ?>
        <dt><?php echo enw_rich($f['info_email_label']); ?></dt>
        <dd><a href="mailto:<?php echo enw_attr($cEmail); ?>"><?php echo htmlspecialchars($cEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></a></dd>
      </dl>
    </div>
    <?php endif; ?>
  </div>
</section>
