<?php
/**
 * @var ?array $user
 * @var string $token
 * @var array $errors
 */
?>
<?php if (!$user): ?>
  <h1>This link no longer works</h1>
  <div class="alert alert-error">The reset link has expired or was already used. Links work once, for 1 hour.</div>
  <a class="btn btn-primary btn-block" href="<?= e(url('/admin/forgot-password')) ?>">Send a new link</a>
<?php else: ?>
  <h1>Choose a new password</h1>
  <p class="muted">For <?= e($user['email']) ?></p>
  <form method="post" action="<?= e(url('/admin/reset-password?token=' . rawurlencode($token))) ?>" class="stack">
    <?= csrf_field() ?>
    <input type="text" name="username" value="<?= e($user['email']) ?>" autocomplete="username" hidden>
    <?= form_field('password', 'password', 'New password', '', ['required' => true, 'error' => $errors['password'] ?? null, 'help' => 'At least 10 characters.', 'attrs' => ['autocomplete' => 'new-password', 'autofocus' => true]]) ?>
    <?= form_field('password', 'password_confirm', 'Repeat new password', '', ['required' => true, 'error' => $errors['password_confirm'] ?? null, 'attrs' => ['autocomplete' => 'new-password']]) ?>
    <button class="btn btn-primary btn-block" type="submit">Save new password</button>
  </form>
<?php endif; ?>
<a class="login-back" href="<?= e(url('/admin/login')) ?>">← Back to sign in</a>
