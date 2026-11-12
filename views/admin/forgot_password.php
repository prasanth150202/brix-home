<?php
/**
 * @var ?string $error
 * @var string $email
 * @var bool $sent
 * @var ?string $devLink  Reset link shown only when app.debug is on and the email could not be sent.
 */
?>
<h1>Reset your password</h1>
<?php if ($sent): ?>
  <div class="alert alert-success">If <strong><?= e($email) ?></strong> belongs to an admin account, a reset link is on its way. It works for 1 hour.</div>
  <p class="muted">No email after a few minutes? Check the spam folder, or ask someone with server access to run <code>php bin/reset-password.php</code>.</p>
  <?php if ($devLink): ?>
    <div class="alert alert-warning">Debug mode: this server could not send email, so here is the link.<br><a href="<?= e($devLink) ?>">Open the reset link</a></div>
  <?php endif; ?>
<?php else: ?>
  <p class="muted">Enter the email you sign in with and we will send you a link to choose a new password.</p>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="<?= e(url('/admin/forgot-password')) ?>" class="stack">
    <?= csrf_field() ?>
    <div class="field">
      <label for="email">Email</label>
      <input id="email" type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Send reset link</button>
  </form>
<?php endif; ?>
<a class="login-back" href="<?= e(url('/admin/login')) ?>">← Back to sign in</a>
