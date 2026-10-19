<?php
if (!isset($lang)) { $lang = 'en'; }
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/contact-mailer.php';

// Email shown on this page comes from the admin (Header & footer); the page's texts and
// layout are blocks, edited in /admin/builder.php?page=contact (live preview).
$siteLayout = enw_layout();
$contactEmail = $siteLayout['footer']['email'] !== '' ? $siteLayout['footer']['email'] : $siteLayout['contact_recipient'];

// The form's texts: from the page's "Contact form" block, with the old keys as a fallback.
$cf = [];
foreach (enw_page_blocks('contact') as $block) {
    if ($block['type'] === 'contact-form') {
        $cf = $block['fields'];
        break;
    }
}
$cfText = function ($key, $legacyKey) use ($cf) {
    return (isset($cf[$key]) && $cf[$key] !== '') ? $cf[$key] : t($legacyKey);
};

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
        $formErrors[] = $cfText('error_name', 'contact.form.error_name');
    }
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = $cfText('error_email', 'contact.form.error_email');
    }
    if ($values['message'] === '') {
        $formErrors[] = $cfText('error_message', 'contact.form.error_message');
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
            $sendError = (isset($cf['error_send']) && $cf['error_send'] !== '')
                ? $cf['error_send']
                : 'Sorry, your message could not be sent. Please email us directly at {email}.';
            $formErrors[] = str_replace('{email}', $contactEmail, $sendError);
        }
    }
}

// Hand the form's state to the "Contact form" block, which draws it.
$GLOBALS['enw_contact'] = ['submitted' => $formSubmitted, 'errors' => $formErrors, 'values' => $values];

require_once __DIR__ . '/includes/header.php';
enw_render_blocks('contact');
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
