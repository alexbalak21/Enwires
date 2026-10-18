<?php
// HOW TO USE
//   1. Copy this file to  includes/mail-config.php  (same folder) and edit it.
//      (Your real mail-config.php is never overwritten by updates.)
//   2. Find working values with  /admin/system-check.php  -> "Mail check":
//      type the settings there, send a test, and copy the ones that work.
//
// This file contains your mail password. It sits in includes/, which the web
// server refuses to serve, and PHP only returns an array — nothing is printed.

return [

    // 'mail' = PHP's built-in mail() (needs a mail system on the server; the
    //          result is uncertain: "accepted" does not mean "delivered").
    // 'smtp' = a real mail server (reliable, errors are explained).
    'transport' => 'mail',

    // The address mail is sent FROM. It should be a real mailbox of your domain.
    // Leave '' for automatic (the SMTP login if it is an address, else contact@<your domain>).
    'from_address' => '',
    'from_name'    => 'Enwires Website',

    // mail() only: also tell the mail system the sender (-f). Some hosts need it,
    // others reject it. Try both in the Mail check.
    'envelope_sender' => false,

    // Password-reset emails link to <this>/reset-password.php. Leave '' to use your SITE_URL
    // (config.php) + /admin. For local testing: 'http://localhost:8000/admin'.
    'admin_url' => '',

    // Used when 'transport' is 'smtp'. Typical values:
    //   port 587 + secure 'tls'  (STARTTLS)   <- most common
    //   port 465 + secure 'ssl'
    //   port 25  + secure 'none'              (often blocked, avoid)
    'smtp' => [
        'host'        => 'smtp.example.com',
        'port'        => 587,
        'secure'      => 'tls',
        'username'    => 'contact@enwires.com',
        'password'    => '',
        'timeout'     => 15,
        'verify_peer' => true,
    ],
];
