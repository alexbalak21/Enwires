<?php
// Mail sending for the admin panel (password-reset emails). Uses the shared
// mail library in includes/mailer.php, so it follows includes/mail-config.php
// (mail() or SMTP). Test it in /admin/system-check.php -> "Mail check".
// Every caller only uses send_email($to, $subject, $body).

require_once dirname(__DIR__, 2) . '/includes/mailer.php';

function send_email($to, $subject, $body) {
    $res = enw_send_mail($to, $subject, $body, ['from_name' => 'Enwires Admin']);
    if (!$res['ok']) {
        error_log('Enwires admin mail to ' . $to . ' failed via ' . $res['transport'] . ': ' . $res['error']);
    }
    return $res['ok'];
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
