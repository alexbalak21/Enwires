<?php
if (!isset($lang)) { $lang = 'en'; }
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/contact-mailer.php';

// Email / location shown on this page come from the admin (Header & footer settings).
$siteLayout = enw_layout();
$contactEmail = $siteLayout['footer']['email'] !== '' ? $siteLayout['footer']['email'] : $siteLayout['contact_recipient'];
$contactLocation = $siteLayout['footer']['location'];

$pageTitle = t('contact.meta.title');
$pageDescription = t('contact.meta.description');
$pageKeywords = t('contact.meta.keywords');

$formSubmitted = false;
$formErrors = [];
$values = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];

// Shown if the mail server refuses the message (kept here so it doesn't
// depend on the CMS content files).
$sendErrors = [
    'en' => 'Sorry, your message could not be sent. Please try again or email us directly at ' . $contactEmail . '.',
    'fr' => "Désolé, votre message n'a pas pu être envoyé. Veuillez réessayer ou nous écrire directement à " . $contactEmail . '.',
    'zh' => '抱歉，您的留言发送失败。请稍后重试，或直接发送邮件至 ' . $contactEmail . '。',
    'ja' => '申し訳ございません。メッセージを送信できませんでした。もう一度お試しいただくか、' . $contactEmail . ' まで直接ご連絡ください。',
];

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

    // Honeypot: real visitors never see/fill this field, bots usually do.
    // Pretend success so the bot doesn't retry.
    if (!empty($_POST['website'])) {
        $formSubmitted = true;
        $values = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
    } elseif (empty($formErrors)) {
        if (send_contact_email($values, CURRENT_LANG)) {
            $formSubmitted = true;
            $values = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
        } else {
            // Keep what the visitor typed so they don't lose their message.
            $formErrors[] = $sendErrors[CURRENT_LANG] ?? $sendErrors['en'];
        }
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

      <form action="<?php echo PAGE_BASE; ?>contact" method="post" novalidate>
        <div style="position:absolute; left:-9999px;" aria-hidden="true">
          <label for="website">Leave this field empty</label>
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>
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
        <?php if ($contactLocation !== ''): ?>
        <dt><?php echo t('contact.info.location_label'); ?></dt>
        <dd><?php echo htmlspecialchars($contactLocation); ?></dd>
        <?php endif; ?>
        <dt><?php echo t('contact.info.email_label'); ?></dt>
        <dd><a href="mailto:<?php echo enw_attr($contactEmail); ?>"><?php echo htmlspecialchars($contactEmail); ?></a></dd>
      </dl>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
