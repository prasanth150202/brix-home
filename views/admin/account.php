<?php
/**
 * @var array $me
 * @var array $errors
 */
?>
<form method="post" class="card narrow-card">
  <?= csrf_field() ?>
  <div class="card-head"><h2>Profile</h2></div>
  <?= form_field('text', 'name', 'Name', $me['name'], ['required' => true, 'error' => $errors['name'] ?? null]) ?>
  <div class="field"><label>Email</label><input type="email" value="<?= e($me['email']) ?>" disabled><p class="field-help">Ask an administrator to change your email.</p></div>

  <div class="card-head card-head-sub"><h2>Change password</h2></div>
  <?= form_field('password', 'current_password', 'Current password', '', ['error' => $errors['current_password'] ?? null, 'attrs' => ['autocomplete' => 'current-password']]) ?>
  <?= form_field('password', 'password', 'New password', '', ['error' => $errors['password'] ?? null, 'help' => 'At least 10 characters. Leave empty to keep your password.', 'attrs' => ['autocomplete' => 'new-password']]) ?>
  <?= form_field('password', 'password_confirm', 'Repeat new password', '', ['error' => $errors['password_confirm'] ?? null, 'attrs' => ['autocomplete' => 'new-password']]) ?>
  <button class="btn btn-primary" type="submit">Save</button>
</form>
