<?php
// Sends contact-form submissions to the Enwires inbox.
//
// The recipient is hardcoded for now. Later, replace CONTACT_RECIPIENT's
// value with a lookup from the database (e.g. a settings table) — nothing
// else needs to change.

define('CONTACT_RECIPIENT', 'contact@enwires.com');

// Sender address MUST be on your own domain (enwires.com). Hosts and mail
// providers reject or spam-folder mail sent "From" an arbitrary visitor
// address (SPF/DKIM/DMARC). The visitor's address goes in Reply-To instead,
// so hitting "Reply" in your mail client answers the visitor.
define('CONTACT_FROM_ADDRESS', 'no-reply@enwires.com');
define('CONTACT_FROM_NAME', 'Enwires Website');

/** Strip CR/LF so user input can never inject extra mail headers. */
function contact_clean_header($value) {
    return trim(preg_replace('/[\r\n]+/', ' ', (string) $value));
}

/** RFC 2047 encode a header value so UTF-8 (fr / zh / ja) survives. */
function contact_encode_header($value) {
    $value = contact_clean_header($value);
    return preg_match('/[^\x20-\x7E]/', $value)
        ? '=?UTF-8?B?' . base64_encode($value) . '?='
        : $value;
}

/**
 * @param array $data name, email, phone, subject, message (already validated)
 * @param string $lang current site language, shown in the email for context
 * @return bool true if the mail was accepted by the local mail transport
 */
function send_contact_email(array $data, $lang = 'en') {
    $name    = contact_clean_header($data['name']);
    $email   = contact_clean_header($data['email']);
    $phone   = contact_clean_header($data['phone']);
    $subject = contact_clean_header($data['subject']);

    $mailSubject = '[Enwires website] ' . ($subject !== '' ? $subject : 'New contact message');

    $body = "New message from the Enwires website contact form\r\n"
          . "--------------------------------------------------\r\n"
          . "Name:     $name\r\n"
          . "Email:    $email\r\n"
          . "Phone:    " . ($phone !== '' ? $phone : '-') . "\r\n"
          . "Subject:  " . ($subject !== '' ? $subject : '-') . "\r\n"
          . "Language: $lang\r\n"
          . "--------------------------------------------------\r\n\r\n"
          . str_replace(["\r\n", "\r"], "\n", $data['message']) . "\r\n";
    // Normalise line endings to CRLF for the mail body.
    $body = preg_replace('/\r\n|\r|\n/', "\r\n", $body);

    $headers = [
        'From'         => contact_encode_header(CONTACT_FROM_NAME) . ' <' . CONTACT_FROM_ADDRESS . '>',
        'Reply-To'     => contact_encode_header($name) . ' <' . $email . '>',
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
        'X-Mailer'     => 'PHP/' . phpversion(),
    ];
    $headerString = '';
    foreach ($headers as $k => $v) {
        $headerString .= "$k: $v\r\n";
    }

    // 5th argument sets the envelope sender (-f), which many hosts require
    // before they will relay mail; it also makes bounces go to a real mailbox.
    $sent = @mail(
        CONTACT_RECIPIENT,
        contact_encode_header($mailSubject),
        $body,
        $headerString,
        '-f' . CONTACT_FROM_ADDRESS
    );

    if (!$sent) {
        error_log('Enwires contact form: mail() failed for message from ' . $email);
    }
    return $sent;
}