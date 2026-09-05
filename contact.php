<?php
$pageTitle = 'Contact';
$pageDescription = 'Get in touch with Enwires in Grenoble, France, to discuss SiBoost for your battery anode material needs.';
$pageKeywords = 'contact Enwires, Enwires Grenoble, battery materials contact, SiBoost inquiry, graphite silicon composite supplier';

$formSubmitted = false;
$formErrors = [];
$values = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    if ($values['name'] === '') {
        $formErrors[] = 'Please enter your name.';
    }
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = 'Please enter a valid email address.';
    }
    if ($values['message'] === '') {
        $formErrors[] = 'Please enter a message.';
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
    <p class="hero-eyebrow">Contact</p>
    <h1 style="font-size:clamp(2rem,4.5vw,3rem);">Let's talk about your graphite.</h1>
    <p style="color:var(--ink-dim); max-width:52ch;">Whether you're evaluating
      SiBoost for a new cell design or have a graphite grade you'd like us to
      look at, send us a note and we'll get back to you.</p>
  </div>
</section>

<section class="band band-paper">
  <div class="wrap split">
    <div>
      <?php if ($formSubmitted): ?>
        <div class="alert alert-success">Thanks &mdash; your message has been received. We'll be in touch shortly.</div>
      <?php elseif (!empty($formErrors)): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars(implode(' ', $formErrors)); ?>
        </div>
      <?php endif; ?>

      <form action="contact.php" method="post" novalidate>
        <div class="form-grid">
          <div class="field">
            <label for="name">Your name *</label>
            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($values['name']); ?>">
          </div>
          <div class="field">
            <label for="phone">Your phone number</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($values['phone']); ?>">
          </div>
          <div class="field full">
            <label for="email">Your email *</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($values['email']); ?>">
          </div>
          <div class="field full">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($values['subject']); ?>">
          </div>
          <div class="field full">
            <label for="message">Message *</label>
            <textarea id="message" name="message" required><?php echo htmlspecialchars($values['message']); ?></textarea>
          </div>
        </div>
        <p class="form-note">* required</p>
        <button type="submit" class="btn btn-primary">Send message</button>
      </form>
    </div>

    <div class="contact-info">
      <dl>
        <dt>Location</dt>
        <dd><?php echo SITE_LOCATION; ?></dd>
        <dt>Email</dt>
        <dd><a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a></dd>
      </dl>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>