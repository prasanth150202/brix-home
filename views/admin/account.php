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

  <div class="card-head card-head-sub"><h2>Sign-in details</h2></div>
  <?= form_field('email', 'email', 'Email', $me['email'], ['required' => true, 'error' => $errors['email'] ?? null, 'help' => 'You sign in with this email.', 'attrs' => ['autocomplete' => 'username']]) ?>
  <?= form_field('password', 'password', 'New password', '', ['error' => $errors['password'] ?? null, 'help' => 'At least 10 characters. Leave empty to keep your password.', 'attrs' => ['autocomplete' => 'new-password']]) ?>
  <?= form_field('password', 'password_confirm', 'Repeat new password', '', ['error' => $errors['password_confirm'] ?? null, 'attrs' => ['autocomplete' => 'new-password']]) ?>

  <div class="card-head card-head-sub"><h2>Confirm it's you</h2></div>
  <?= form_field('password', 'current_password', 'Current password', '', ['error' => $errors['current_password'] ?? null, 'help' => 'Needed to change your email or password.', 'attrs' => ['autocomplete' => 'current-password']]) ?>
  <button class="btn btn-primary" type="submit">Save</button>
</form>
