<?php
/**
 * @var ?array $user
 * @var array $errors
 */
?>
<div class="page-head">
  <a class="back-link" href="<?= e(url('/admin/users')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?>Users</a>
</div>
<form method="post" class="card narrow-card">
  <?= csrf_field() ?>
  <?= form_field('text', 'name', 'Name', $user['name'] ?? '', ['required' => true, 'error' => $errors['name'] ?? null]) ?>
  <?= form_field('email', 'email', 'Email', $user['email'] ?? '', ['required' => true, 'error' => $errors['email'] ?? null]) ?>
  <?= form_field('select', 'role', 'Role', $user['role'] ?? 'editor', ['options' => ['admin' => 'Administrator', 'editor' => 'Editor'], 'error' => $errors['role'] ?? null]) ?>
  <?= form_field('password', 'password', $user ? 'New password' : 'Password', '', [
      'required' => $user === null,
      'error'    => $errors['password'] ?? null,
      'help'     => $user ? 'Leave empty to keep the current password.' : 'At least 10 characters.',
      'attrs'    => ['autocomplete' => 'new-password'],
  ]) ?>
  <button class="btn btn-primary" type="submit"><?= $user ? 'Save user' : 'Create user' ?></button>
</form>
