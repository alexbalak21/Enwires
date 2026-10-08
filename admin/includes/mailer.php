<?php
// Minimal mail sending for the admin panel (currently: password reset
// emails only). Uses PHP's built-in mail() by default — no external
// dependencies, no Composer, no SMTP credentials to configure.
//
// IMPORTANT: PHP's mail() depends on a mail transport being configured on
// the server (sendmail/postfix locally, or your host's mail service). On
// many hosts this works out of the box; on others (and on most local dev
// setups) it silently does nothing. If reset emails aren't arriving in
// production, that's the first thing to check — and the fix is almost
// always on the hosting/server side, not in this code.
//
// To switch to SMTP later (recommended for reliability in production):
// replace the body of send_email() with a PHPMailer/Symfony Mailer call.
// Nothing else in the app needs to change — every caller only ever uses
// send_email($to, $subject, $body).

define('MAIL_FROM_ADDRESS', 'no-reply@enwires.com');
define('MAIL_FROM_NAME', 'Enwires Admin');

function send_email($to, $subject, $body) {
    $headers = [
        'From' => MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        'Content-Type' => 'text/plain; charset=UTF-8',
    ];
    $headerString = '';
    foreach ($headers as $k => $v) {
        $headerString .= "$k: $v\r\n";
    }

    return @mail($to, $subject, $body, $headerString);
}

function send_password_reset_email($email, $username, $resetLink) {
    $subject = 'Reset your Enwires admin password';
    $body = "Hi $username,\n\n"
        . "A password reset was requested for your Enwires admin account.\n\n"
        . "Reset your password here (this link expires in " . RESET_TOKEN_TTL_MINUTES . " minutes):\n"
        . "$resetLink\n\n"
        . "If you didn't request this, you can safely ignore this email — your password will not be changed.\n";

    return send_email($email, $subject, $body);
}
