<?php
if (!isset($lang)) { $lang = 'en'; }
require_once __DIR__ . '/includes/config.php';

$pageTitle = t('contact.meta.title');
$pageDescription = t('contact.meta.description');
$pageKeywords = t('contact.meta.keywords');

$formSubmitted = false;
$formErrors = [];
$values = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    if ($values['name'] === '') {
        $formErrors[] = t('contact.form.error_name');
    }
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = t('contact.form.error_email');
    }
    if ($values['message'] === '') {
        $formErrors[] = t('contact.form.error_message');
    }

    if (empty($formErrors)) {
        // TODO: wire up a real mailer (SMTP / mail()) once credentials are available.
        // Left unsent intentionally — no mail transport configured yet.
        $formSubmitted = true;
        $values = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="band band-charcoal" style="padding-top:64px; padding-bottom:56px;">
  <div class="wrap">
    <p class="hero-eyebrow"><?php echo t('contact.hero.eyebrow'); ?></p>
    <h1 style="font-size:clamp(2rem,4.5vw,3rem);"><?php echo t('contact.hero.title'); ?></h1>
    <p style="color:var(--ink-dim); max-width:52ch;"><?php echo t('contact.hero.text'); ?></p>
  </div>
</section>

<section class="band band-paper">
  <div class="wrap split reveal">
    <div>
      <?php if ($formSubmitted): ?>
        <div class="alert alert-success"><?php echo t('contact.form.success'); ?></div>
      <?php elseif (!empty($formErrors)): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars(implode(' ', $formErrors)); ?>
        </div>
      <?php endif; ?>

      <form action="<?php echo PAGE_BASE; ?>contact.php" method="post" novalidate>
        <div class="form-grid">
          <div class="field">
            <label for="name"><?php echo t('contact.form.name_label'); ?></label>
            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($values['name']); ?>">
          </div>
          <div class="field">
            <label for="phone"><?php echo t('contact.form.phone_label'); ?></label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($values['phone']); ?>">
          </div>
          <div class="field full">
            <label for="email"><?php echo t('contact.form.email_label'); ?></label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($values['email']); ?>">
          </div>
          <div class="field full">
            <label for="subject"><?php echo t('contact.form.subject_label'); ?></label>
            <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($values['subject']); ?>">
          </div>
          <div class="field full">
            <label for="message"><?php echo t('contact.form.message_label'); ?></label>
            <textarea id="message" name="message" required><?php echo htmlspecialchars($values['message']); ?></textarea>
          </div>
        </div>
        <p class="form-note"><?php echo t('contact.form.required_note'); ?></p>
        <button type="submit" class="btn btn-primary"><?php echo t('contact.form.submit'); ?></button>
      </form>
    </div>

    <div class="contact-info">
      <dl>
        <dt><?php echo t('contact.info.location_label'); ?></dt>
        <dd><?php echo SITE_LOCATION; ?></dd>
        <dt><?php echo t('contact.info.email_label'); ?></dt>
        <dd><a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a></dd>
      </dl>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
