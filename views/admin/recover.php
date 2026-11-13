<?php
/**
 * TEMPORARY: see app/controllers/admin/recover.php. Delete after use.
 *
 * @var array $errors
 * @var string $email
 */
?>
<h1>Recover admin access</h1>
<p class="muted">Sets a new sign-in email and password for the main admin account. Works once.</p>
<form method="post" action="<?= e(url('/admin/recover')) ?>" class="stack" autocomplete="off">
  <?= csrf_field() ?>
  <?= form_field('password', 'key', 'Recovery key', '', ['required' => true, 'error' => $errors['key'] ?? null, 'attrs' => ['autocomplete' => 'off', 'autofocus' => true]]) ?>
  <?= form_field('email', 'email', 'New admin email', $email, ['required' => true, 'error' => $errors['email'] ?? null, 'attrs' => ['autocomplete' => 'username']]) ?>
  <?= form_field('password', 'password', 'New password', '', ['required' => true, 'error' => $errors['password'] ?? null, 'help' => 'At least 10 characters.', 'attrs' => ['autocomplete' => 'new-password']]) ?>
  <?= form_field('password', 'password_confirm', 'Repeat new password', '', ['required' => true, 'error' => $errors['password_confirm'] ?? null, 'attrs' => ['autocomplete' => 'new-password']]) ?>
  <button class="btn btn-primary btn-block" type="submit">Set email and password</button>
</form>
<a class="login-back" href="<?= e(url('/admin/login')) ?>">← Back to sign in</a>
