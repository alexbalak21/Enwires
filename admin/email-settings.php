<?php
// Email settings: who receives the messages from the contact form, and who they are
// sent from. Saved in content/site.json (next to the header/footer settings).
//
// How the mail is DELIVERED (PHP mail() or an SMTP server, and the SMTP password)
// stays in includes/mail-config.php: a password does not belong in a web form.
// To try delivery settings, use System check -> Mail check.

require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/includes/layout-store.php';      // enw_site_load(), ls_site_save()
require_once dirname(__DIR__) . '/includes/mailer.php';    // enw_send_mail(), enw_mail_config() ...

function es_h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Fingerprint of the three saved values: detects that someone else changed them meanwhile. */
function es_rev() {
    $s = enw_site_load();
    return sha1(json_encode([$s['contact_recipient'] ?? null, $s['mail_from_address'] ?? null, $s['mail_from_name'] ?? null]));
}

$cfg = enw_mail_config();
$notice = null;        // [level, text]
$testResult = null;
$posted = null;        // values to show again when the form has errors

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if (!admin_verify_csrf()) {
        $notice = ['bad', 'Your session expired — please try again.'];

    } elseif ($action === 'save') {
        $recipient = trim((string) ($_POST['recipient'] ?? ''));
        $from      = trim((string) ($_POST['from_address'] ?? ''));
        $name      = trim((string) preg_replace('/[\r\n]+/', ' ', (string) ($_POST['from_name'] ?? '')));
        $name      = function_exists('mb_substr') ? mb_substr($name, 0, 80, 'UTF-8') : substr($name, 0, 80);

        $errors = [];
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'The address that receives the messages is not a valid email address (messages would be lost).';
        }
        if ($from !== '' && !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'The sender address is not a valid email address. Leave it empty for the automatic one.';
        }
        if (!hash_equals(es_rev(), (string) ($_POST['rev'] ?? ''))) {
            $errors[] = 'Someone else changed these settings while this page was open. Reload the page to see their values.';
        }

        if ($errors) {
            $notice = ['bad', implode(' ', $errors)];
            $posted = ['recipient' => $recipient, 'from' => $from, 'name' => $name];
        } else {
            $site = enw_site_load();
            $site['contact_recipient'] = $recipient;
            $site['mail_from_address'] = $from;
            $site['mail_from_name']    = $name;
            if (ls_site_save($site)) {
                $notice = ['ok', 'Saved. New messages from the contact form use these settings straight away. Use “Send a test email” below to check.'];
            } else {
                $notice = ['bad', 'Could not save: content/site.json is not writable by the web server. Open System check → File & SQLite check to see what to fix.'];
                $posted = ['recipient' => $recipient, 'from' => $from, 'name' => $name];
            }
        }

    } elseif ($action === 'test') {
        $shared = enw_layout_shared();
        $testResult = enw_send_mail(
            $shared['contact_recipient'],
            'Enwires email test ' . date('Y-m-d H:i:s'),
            "This is a test from Admin > Email settings.\n\nIf you can read this, contact-form messages will reach this address.\n"
        );
    }
}

// What is saved now (read again after a save).
$shared    = enw_layout_shared();
$saved     = ['recipient' => $shared['contact_recipient'], 'from' => $shared['mail_from_address'], 'name' => $shared['mail_from_name']];
$show      = $posted ?: $saved;
$autoFrom  = enw_mail_auto_from($cfg);
$nowFrom   = enw_mail_pick_from($cfg, '');
$nowName   = $saved['name'] !== '' ? $saved['name'] : (string) $cfg['from_name'];
$transport = ($cfg['transport'] === 'smtp')
    ? 'SMTP server ' . ($cfg['smtp']['host'] !== '' ? $cfg['smtp']['host'] . ':' . $cfg['smtp']['port'] : '(host not set)')
    : 'PHP mail()';
$siteDomain = enw_mail_site_domain();
$fromDomain = strpos($nowFrom, '@') !== false ? strtolower(substr(strrchr($nowFrom, '@'), 1)) : '';

$pageTitle = 'Email settings';
require __DIR__ . '/includes/layout-header.php';
?>
<style>
  .es-hint { font-size: .8rem; color: var(--ink-dim); margin-top: 4px; }
  .es-form input[type="email"], .es-form input[type="text"] { width: 100%; box-sizing: border-box; padding: 9px 11px; border: 1px solid var(--line); border-radius: 4px; font: inherit; }
  .es-flow { display: grid; grid-template-columns: auto 1fr; gap: 6px 14px; font-size: .95rem; }
  .es-flow dt { color: var(--ink-dim); }
  .es-flow dd { margin: 0; }
  .es-pre { background: #1e1e24; color: #e6e6ea; padding: 12px 14px; border-radius: 6px; overflow: auto; font-size: .8rem; line-height: 1.5; max-height: 320px; }
</style>

<a href="index.php" class="back-link">&larr; Back to dashboard</a>
<h1>Email settings</h1>
<p class="subtitle">Who receives the messages from the contact form, and who they are sent from.</p>

<?php if ($notice): ?>
  <div class="alert <?php echo $notice[0] === 'ok' ? 'alert-success' : 'alert-error'; ?>"><?php echo es_h($notice[1]); ?></div>
<?php endif; ?>

<div class="panel">
  <form method="post" class="es-form">
    <?php echo admin_csrf_field(); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="rev" value="<?php echo es_h(es_rev()); ?>">

    <div class="field" style="margin-bottom:20px;">
      <label class="field-label" for="recipient">Send contact-form messages to</label>
      <input type="email" id="recipient" name="recipient" required value="<?php echo es_h($show['recipient']); ?>">
      <div class="es-hint">Every message from the Contact page arrives at this address. Double-check it: a typo here means lost messages.</div>
    </div>

    <div class="field" style="margin-bottom:20px;">
      <label class="field-label" for="from_address">Send from (sender address)</label>
      <input type="text" id="from_address" name="from_address" value="<?php echo es_h($show['from']); ?>" placeholder="<?php echo es_h($autoFrom); ?>">
      <div class="es-hint">
        Should be a <strong>real mailbox of your own domain</strong>: mail servers reject or hide mail from invented or foreign addresses.
        Empty = automatic (currently <code><?php echo es_h($autoFrom); ?></code>).
      </div>
    </div>

    <div class="field" style="margin-bottom:20px;">
      <label class="field-label" for="from_name">Sender name</label>
      <input type="text" id="from_name" name="from_name" maxlength="80" value="<?php echo es_h($show['name']); ?>" placeholder="<?php echo es_h($cfg['from_name']); ?>">
      <div class="es-hint">The name shown next to the address in the inbox. Empty = <code><?php echo es_h($cfg['from_name']); ?></code>.</div>
    </div>

    <button type="submit" class="btn-primary">Save</button>
  </form>
</div>

<div class="panel">
  <h2 style="margin-top:0;">How a contact message will look</h2>
  <dl class="es-flow" style="margin:0;">
    <dt>From</dt><dd><code><?php echo es_h($nowName . ' <' . $nowFrom . '>'); ?></code></dd>
    <dt>To</dt><dd><code><?php echo es_h($saved['recipient']); ?></code></dd>
    <dt>Reply-To</dt><dd>the visitor's address, so pressing “Reply” answers them directly</dd>
    <dt>Delivered by</dt><dd><?php echo es_h($transport); ?></dd>
  </dl>
  <?php if ($siteDomain !== '' && $fromDomain !== '' && $fromDomain !== $siteDomain): ?>
    <div class="alert alert-error" style="margin:14px 0 0;">
      The sender address is on <strong><?php echo es_h($fromDomain); ?></strong>, not on your site's domain <strong><?php echo es_h($siteDomain); ?></strong>.
      That is fine if your mail provider allows it, but many servers only accept senders of the domain they host — and mail may go to spam without matching SPF records.
    </div>
  <?php endif; ?>
</div>

<div class="panel">
  <h2 style="margin-top:0;">Send a test email</h2>
  <p style="margin-top:0;">Sends a real message to <code><?php echo es_h($saved['recipient']); ?></code> with the <strong>saved</strong> settings above (save first if you just changed them).</p>
  <form method="post">
    <?php echo admin_csrf_field(); ?>
    <input type="hidden" name="action" value="test">
    <button type="submit" class="btn-secondary">Send a test email</button>
  </form>

  <?php if ($testResult !== null): ?>
    <div class="alert <?php echo $testResult['ok'] ? 'alert-success' : 'alert-error'; ?>" style="margin-top:16px;">
      <?php if ($testResult['ok']): ?>
        ✅ <strong>Accepted</strong> via <?php echo es_h($testResult['transport']); ?>. Now check the inbox <em>and the spam folder</em> of <code><?php echo es_h($testResult['to']); ?></code>.
        <?php if ($testResult['transport'] === 'mail'): ?>
          <br><small>With PHP <code>mail()</code>, “accepted” only means this server took the message; it cannot confirm delivery. If it never arrives, switch to SMTP (see below).</small>
        <?php endif; ?>
      <?php else: ?>
        ❌ <strong>Not sent.</strong> <?php echo es_h($testResult['error']); ?>
        <br><small>For a detailed diagnosis open <a href="system-check.php#mail">System check → Mail check</a>.</small>
      <?php endif; ?>
    </div>
    <details>
      <summary>Show the conversation</summary>
      <pre class="es-pre"><?php echo es_h(implode("\n", $testResult['log'])); ?></pre>
    </details>
  <?php endif; ?>
</div>

<div class="panel">
  <h2 style="margin-top:0;">How the mail is delivered</h2>
  <p style="margin-top:0;">
    Currently: <strong><?php echo es_h($transport); ?></strong>.
    <?php if (!is_file(dirname(__DIR__) . '/includes/mail-config.php')): ?>
      (No <code>includes/mail-config.php</code> yet, so the defaults are used.)
    <?php endif; ?>
  </p>
  <p style="margin-bottom:0;">
    This is set in the file <code>includes/mail-config.php</code> because it can hold an SMTP password.
    Try delivery settings (PHP <code>mail()</code> or SMTP) in <a href="system-check.php#mail">System check → Mail check</a>,
    which also shows the exact settings to copy into that file.
    The address shown <em>on the website</em> (footer and Contact page) is a different setting: see <a href="layout.php">Header &amp; footer</a>.
  </p>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
