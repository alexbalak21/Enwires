<?php
// Sends contact-form submissions to the Enwires inbox.
//
// Recipient : the "Contact form" address set in /admin/layout.php (stored in
//             content/site.json); until it is saved, SITE_EMAIL from config.php.
// Transport : mail() or SMTP, chosen in includes/mail-config.php.
// Test it   : /admin/system-check.php  ->  "Mail check".

require_once __DIR__ . '/mailer.php';

define('CONTACT_RECIPIENT', enw_layout_shared()['contact_recipient']);

/**
 * @param array  $data name, email, phone, subject, message (already validated)
 * @param string $lang current site language, shown in the email for context
 * @return bool true if the mail server / mail system accepted the message
 */
function send_contact_email(array $data, $lang = 'en') {
    $name    = enw_mail_clean($data['name']);
    $email   = enw_mail_clean($data['email']);
    $phone   = enw_mail_clean($data['phone']);
    $subject = enw_mail_clean($data['subject']);

    $mailSubject = '[Enwires website] ' . ($subject !== '' ? $subject : 'New contact message');

    $body = "New message from the Enwires website contact form\n"
          . "--------------------------------------------------\n"
          . "Name:     $name\n"
          . "Email:    $email\n"
          . "Phone:    " . ($phone !== '' ? $phone : '-') . "\n"
          . "Subject:  " . ($subject !== '' ? $subject : '-') . "\n"
          . "Language: $lang\n"
          . "--------------------------------------------------\n\n"
          . (string) $data['message'] . "\n";

    // The mail is sent FROM your own domain (mail servers reject mail that claims to
    // come from a visitor's address); the visitor is in Reply-To, so "Reply" answers them.
    $res = enw_send_mail(CONTACT_RECIPIENT, $mailSubject, $body, [
        'reply_to' => ['email' => $email, 'name' => $name],
    ]);

    $GLOBALS['enw_last_mail'] = $res;
    if (!$res['ok']) {
        error_log('Enwires contact form: sending failed via ' . $res['transport'] . ' — ' . $res['error']);
    }
    return $res['ok'];
}
