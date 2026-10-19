<?php
// Shared mail sending: used by the contact form, the admin password reset and
// the "Mail check" in /admin/system-check.php.
//
// Two ways to send, chosen in includes/mail-config.php (copy the sample file):
//   'mail'  PHP's built-in mail(). Needs a mail system on the server. It only
//           tells us the SERVER ACCEPTED the message — not that it was delivered.
//   'smtp'  Talks to a real mail server (your host's, Gmail, Brevo ...). The
//           server's answer is known, so errors are reported exactly. This is
//           the reliable choice on most hosts.
//
// Every function returns a result array and never throws; the reason of a
// failure is in $result['error'] and the conversation is in $result['log'].

// The site constants (SITE_URL, SITE_EMAIL ...) live in config.php.
if (!defined('SITE_URL')) {
    if (!isset($lang)) {
        $lang = 'en';
    }
    $enwMailConfigFile = __DIR__ . '/config.php';
    if (is_file($enwMailConfigFile)) {
        require_once $enwMailConfigFile;
    }
}

// Settings saved in the admin (Email settings) live in content/site.json.
require_once __DIR__ . '/layout-core.php';

// --- configuration ------------------------------------------------------------

function enw_mail_defaults() {
    return [
        'transport'       => 'mail',     // 'mail' or 'smtp'
        'from_address'    => '',         // '' = automatic (see enw_mail_pick_from)
        'from_name'       => 'Enwires Website',
        'envelope_sender' => false,      // mail() only: also pass -f<from_address>
        'admin_url'       => '',         // base URL of the admin for reset links; '' = SITE_URL + /admin
        'smtp' => [
            'host'        => '',
            'port'        => 587,
            'secure'      => 'tls',      // 'tls' (STARTTLS, port 587), 'ssl' (port 465) or 'none' (port 25)
            'username'    => '',
            'password'    => '',
            'timeout'     => 15,
            'verify_peer' => true,       // check the server's certificate
        ],
    ];
}

/** Defaults overridden by includes/mail-config.php (if you created it). */
function enw_mail_config() {
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $cfg = enw_mail_defaults();
    $file = __DIR__ . '/mail-config.php';
    if (is_file($file)) {
        try {
            $user = include $file;
            if (is_array($user)) {
                $cfg = array_replace_recursive($cfg, $user);
            }
        } catch (Throwable $t) {
            error_log('Enwires mail: includes/mail-config.php could not be read: ' . $t->getMessage());
        }
    }
    return $cfg;
}

/** "enwires.com" from SITE_URL. */
function enw_mail_site_domain() {
    $host = parse_url(defined('SITE_URL') ? SITE_URL : '', PHP_URL_HOST);
    $host = is_string($host) ? strtolower($host) : '';
    return preg_replace('/^www\./', '', $host);
}

/** Sender address / name saved in the admin (Email settings). '' = not set. */
function enw_mail_site_settings() {
    $s = function_exists('enw_site_load') ? enw_site_load() : [];
    $from = (isset($s['mail_from_address']) && is_string($s['mail_from_address'])) ? trim($s['mail_from_address']) : '';
    $name = (isset($s['mail_from_name']) && is_string($s['mail_from_name'])) ? trim($s['mail_from_name']) : '';
    return [
        'from_address' => ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) ? $from : '',
        'from_name'    => $name,
    ];
}

/**
 * The automatic sender, used when none is saved in the admin: the address in
 * mail-config.php, else (with SMTP) the SMTP login if it is an address, else
 * contact@<your site domain>. Mail servers want a REAL mailbox of your domain here.
 */
function enw_mail_auto_from(array $cfg) {
    $conf = trim((string) ($cfg['from_address'] ?? ''));
    if ($conf !== '' && filter_var($conf, FILTER_VALIDATE_EMAIL)) {
        return $conf;
    }
    $user = trim((string) ($cfg['smtp']['username'] ?? ''));
    if (($cfg['transport'] ?? '') === 'smtp' && filter_var($user, FILTER_VALIDATE_EMAIL)) {
        return $user;
    }
    $d = enw_mail_site_domain();
    return 'contact@' . ($d !== '' ? $d : 'localhost');
}

/**
 * The address mail is sent FROM. In order:
 *   1. the address typed for this one send (the System check's test form),
 *   2. the address saved in the admin (Email settings),
 *   3. the automatic one (see enw_mail_auto_from).
 */
function enw_mail_pick_from(array $cfg, $override = '') {
    $override = trim((string) $override);
    if ($override !== '' && filter_var($override, FILTER_VALIDATE_EMAIL)) {
        return $override;
    }
    $site = enw_mail_site_settings();
    if ($site['from_address'] !== '') {
        return $site['from_address'];
    }
    return enw_mail_auto_from($cfg);
}

// --- building the message -----------------------------------------------------

function enw_mail_clean($v) {
    return trim(preg_replace('/[\r\n]+/', ' ', (string) $v));
}

/** RFC 2047: keeps accents / Chinese / Japanese intact in headers. */
function enw_mail_encode_header($v) {
    $v = enw_mail_clean($v);
    return preg_match('/[^\x20-\x7E]/', $v) ? '=?UTF-8?B?' . base64_encode($v) . '?=' : $v;
}

/** "Name <address>" with the name safely encoded. */
function enw_mail_address($name, $email) {
    $name = enw_mail_clean($name);
    if ($name === '') {
        return $email;
    }
    if (preg_match('/[^\x20-\x7E]/', $name)) {
        $name = '=?UTF-8?B?' . base64_encode($name) . '?=';
    } elseif (preg_match('/[()<>\[\]:;@\\\\,."]/', $name)) {
        $name = '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $name) . '"';
    }
    return $name . ' <' . $email . '>';
}

function enw_mail_build($to, $subject, $body, $from, $fromName, $replyTo) {
    $domain = substr(strrchr($from, '@'), 1);
    $headers = [
        'From'                      => enw_mail_address($fromName, $from),
        'Reply-To'                  => '',
        'MIME-Version'              => '1.0',
        'Content-Type'              => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => 'base64',
    ];
    if (is_array($replyTo) && !empty($replyTo['email']) && filter_var($replyTo['email'], FILTER_VALIDATE_EMAIL)) {
        $headers['Reply-To'] = enw_mail_address($replyTo['name'] ?? '', $replyTo['email']);
    } else {
        unset($headers['Reply-To']);
    }

    $bodyB64 = chunk_split(base64_encode(preg_replace('/\r\n|\r|\n/', "\r\n", (string) $body)), 76, "\r\n");
    $subjectEnc = enw_mail_encode_header($subject);

    $raw = 'Date: ' . date('r') . "\r\n"
         . 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . ($domain !== '' ? $domain : 'localhost') . ">\r\n"
         . 'To: ' . $to . "\r\n"
         . 'Subject: ' . $subjectEnc . "\r\n";
    $headerString = '';
    foreach ($headers as $k => $v) {
        $raw .= $k . ': ' . $v . "\r\n";
        $headerString .= $k . ': ' . $v . "\r\n";
    }
    $raw .= "\r\n" . $bodyB64;

    return ['raw' => $raw, 'headers' => $headerString, 'subject' => $subjectEnc, 'body' => $bodyB64];
}

// --- transport 1: PHP mail() --------------------------------------------------

function enw_mail_via_php($to, array $msg, $envelopeFrom, array &$log) {
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (!function_exists('mail') || in_array('mail', $disabled, true)) {
        return [false, 'The PHP function mail() is disabled on this server. Use SMTP instead.'];
    }
    $log[] = '* calling mail() — sender header: ' . trim(strtok($msg['headers'], "\r\n"));
    error_clear_last();
    if ($envelopeFrom !== '') {
        $log[] = '* with envelope sender -f' . $envelopeFrom;
        $ok = @mail($to, $msg['subject'], $msg['body'], $msg['headers'], '-f' . $envelopeFrom);
    } else {
        $ok = @mail($to, $msg['subject'], $msg['body'], $msg['headers']);
    }
    if ($ok) {
        $log[] = '* mail() returned true: the server\'s mail system accepted the message for delivery.';
        $log[] = '* (This does NOT prove it was delivered — check the inbox and the spam folder.)';
        return [true, ''];
    }
    $e = error_get_last();
    $log[] = '* mail() returned false' . ($e ? ' — ' . $e['message'] : '');
    return [false, 'mail() was refused by the server\'s mail system' . ($e ? ': ' . $e['message'] : '') . '.'];
}

// --- transport 2: SMTP ----------------------------------------------------------

/**
 * Send one message through an SMTP server. Returns [bool ok, string error].
 * The whole conversation is appended to $log (the password is never logged).
 */
function enw_smtp_send(array $c, $envFrom, $to, $data, array &$log) {
    $host = trim((string) ($c['host'] ?? ''));
    $port = (int) ($c['port'] ?? 587);
    $secure = (string) ($c['secure'] ?? 'tls');
    $timeout = max(3, (int) ($c['timeout'] ?? 15));
    $verify = !empty($c['verify_peer']);

    if ($host === '') {
        return [false, 'No SMTP host is set.'];
    }
    if (!function_exists('stream_socket_client')) {
        return [false, 'stream_socket_client() is disabled on this server, so SMTP cannot be used.'];
    }
    if (($secure === 'ssl' || $secure === 'tls') && !extension_loaded('openssl')) {
        return [false, 'The PHP openssl extension is not loaded, so encrypted SMTP is not possible.'];
    }

    $ctx = stream_context_create(['ssl' => [
        'verify_peer'      => $verify,
        'verify_peer_name' => $verify,
        'allow_self_signed' => !$verify,
        'SNI_enabled'      => true,
        'peer_name'        => $host,
    ]]);
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $log[] = '* connecting to ' . $remote . ' (security: ' . $secure . ')';

    error_clear_last();
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        $why = $errstr !== '' ? $errstr : (($e = error_get_last()) ? $e['message'] : 'unknown reason');
        $log[] = '* connection failed: ' . $why;
        return [false, 'Could not connect to ' . $host . ':' . $port . ' — ' . $why . ' (' . $errno . ')'];
    }
    stream_set_timeout($fp, $timeout);

    $read = function () use ($fp, &$log) {
        $resp = '';
        while (($line = fgets($fp, 2048)) !== false) {
            $resp .= $line;
            $log[] = 'S: ' . rtrim($line);
            if (strlen($line) < 4 || $line[3] === ' ') break;   // last line of a reply is "250 text"
        }
        return [(int) substr($resp, 0, 3), $resp];
    };
    $cmd = function ($line, $hide = false) use ($fp, &$log, $read) {
        $log[] = 'C: ' . ($hide ? '[hidden]' : $line);
        @fwrite($fp, $line . "\r\n");
        return $read();
    };
    $fail = function ($msg) use ($fp, &$log) {
        $log[] = '* ' . $msg;
        @fwrite($fp, "QUIT\r\n");
        @fclose($fp);
        return [false, $msg];
    };

    list($code, $resp) = $read();
    if ($code === 0) {
        return $fail('The server did not answer (timeout after ' . $timeout . ' s). The port may be blocked or the wrong security mode is selected.');
    }
    if ($code !== 220) {
        return $fail('Unexpected greeting from the server: ' . trim($resp));
    }

    $ehlo = preg_replace('/[^A-Za-z0-9.\-]/', '', (string) ($_SERVER['SERVER_NAME'] ?? gethostname()));
    if ($ehlo === '') $ehlo = 'localhost';

    list($code, $resp) = $cmd('EHLO ' . $ehlo);
    if ($code !== 250) {
        list($code, $resp) = $cmd('HELO ' . $ehlo);
        if ($code !== 250) return $fail('The server refused the greeting: ' . trim($resp));
    }

    if ($secure === 'tls') {
        list($code, $resp) = $cmd('STARTTLS');
        if ($code !== 220) return $fail('The server does not accept STARTTLS here: ' . trim($resp) . ' — try security "ssl" on port 465, or "none".');
        $log[] = '* starting TLS';
        $method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) $method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) $method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        error_clear_last();
        if (!@stream_socket_enable_crypto($fp, true, $method)) {
            $e = error_get_last();
            return $fail('The encrypted (TLS) connection could not be set up' . ($e ? ': ' . $e['message'] : '') . '. If the certificate is the problem, you can untick "verify certificate" (less secure).');
        }
        $log[] = '* TLS established';
        list($code, $resp) = $cmd('EHLO ' . $ehlo);
        if ($code !== 250) return $fail('The server refused the greeting after TLS: ' . trim($resp));
    }

    $user = (string) ($c['username'] ?? '');
    if ($user !== '') {
        $caps = strtoupper($resp);
        $pass = (string) ($c['password'] ?? '');
        if (strpos($caps, 'AUTH') === false) {
            return $fail('A username is set but the server does not offer login here (it often only does after encryption — try security "tls" or "ssl").');
        }
        if (strpos($caps, 'PLAIN') !== false) {
            list($code, $resp) = $cmd('AUTH PLAIN ' . base64_encode("\0" . $user . "\0" . $pass), true);
            if ($code !== 235) return $fail('Login failed (check username and password): ' . trim($resp));
        } elseif (strpos($caps, 'LOGIN') !== false) {
            list($code, $resp) = $cmd('AUTH LOGIN');
            if ($code !== 334) return $fail('Login failed: ' . trim($resp));
            list($code, $resp) = $cmd(base64_encode($user), true);
            if ($code !== 334) return $fail('Login failed: ' . trim($resp));
            list($code, $resp) = $cmd(base64_encode($pass), true);
            if ($code !== 235) return $fail('Login failed (check username and password): ' . trim($resp));
        } else {
            return $fail('The server offers login methods this site does not support. It said: ' . trim($resp));
        }
        $log[] = '* logged in as ' . $user;
    }

    list($code, $resp) = $cmd('MAIL FROM:<' . $envFrom . '>');
    if ($code !== 250) return $fail('The server refused the sender address ' . $envFrom . ': ' . trim($resp));

    list($code, $resp) = $cmd('RCPT TO:<' . $to . '>');
    if ($code !== 250 && $code !== 251) return $fail('The server refused the recipient ' . $to . ': ' . trim($resp));

    list($code, $resp) = $cmd('DATA');
    if ($code !== 354) return $fail('The server did not accept the message: ' . trim($resp));

    $data = preg_replace('/^\./m', '..', rtrim($data, "\r\n"));   // dot-stuffing
    $log[] = 'C: [message: ' . strlen($data) . ' bytes]';
    @fwrite($fp, $data . "\r\n.\r\n");
    list($code, $resp) = $read();
    if ($code !== 250) return $fail('The server refused the message: ' . trim($resp));

    $cmd('QUIT');
    @fclose($fp);
    $log[] = '* the mail server accepted the message: ' . trim($resp);
    return [true, ''];
}

// --- the one function everybody calls ---------------------------------------

/**
 * Send a plain-text email.
 *
 * $opt (all optional): 'reply_to' => ['email' => ..., 'name' => ...],
 *   'from_name', 'from_address', 'transport' ('mail'|'smtp'),
 *   'envelope_sender' (bool), 'smtp' => [host, port, secure, username, password, ...]
 *
 * Returns ['ok' => bool, 'transport', 'from', 'to', 'error', 'log' => [lines]].
 */
function enw_send_mail($to, $subject, $body, array $opt = []) {
    $cfg = enw_mail_config();
    if (isset($opt['smtp']) && is_array($opt['smtp'])) {
        $cfg['smtp'] = array_replace($cfg['smtp'], $opt['smtp']);
    }
    $transport = (isset($opt['transport']) && in_array($opt['transport'], ['mail', 'smtp'], true)) ? $opt['transport'] : $cfg['transport'];
    if (!in_array($transport, ['mail', 'smtp'], true)) $transport = 'mail';
    $cfg['transport'] = $transport;

    $envelope = array_key_exists('envelope_sender', $opt) ? !empty($opt['envelope_sender']) : !empty($cfg['envelope_sender']);
    $siteMail = enw_mail_site_settings();
    if (array_key_exists('from_name', $opt)) {
        $fromName = (string) $opt['from_name'];
    } elseif ($siteMail['from_name'] !== '') {
        $fromName = $siteMail['from_name'];
    } else {
        $fromName = (string) $cfg['from_name'];
    }
    $to = trim((string) $to);
    $from = enw_mail_pick_from($cfg, $opt['from_address'] ?? '');

    $res = ['ok' => false, 'transport' => $transport, 'from' => $from, 'to' => $to, 'error' => '', 'log' => []];

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $res['error'] = 'The recipient "' . $to . '" is not a valid email address.';
        return $res;
    }

    $msg = enw_mail_build($to, $subject, $body, $from, $fromName, $opt['reply_to'] ?? null);

    try {
        if ($transport === 'smtp') {
            list($ok, $err) = enw_smtp_send($cfg['smtp'], $from, $to, $msg['raw'], $res['log']);
        } else {
            list($ok, $err) = enw_mail_via_php($to, $msg, $envelope ? $from : '', $res['log']);
        }
    } catch (Throwable $t) {
        $ok = false;
        $err = 'Unexpected error while sending: ' . $t->getMessage();
    }
    $res['ok'] = $ok;
    $res['error'] = $err;
    return $res;
}
