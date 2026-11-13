<?php
/**
 * @var ?string $error
 * @var string $email
 */
?>
<h1>Sign in to the admin</h1>
<p class="muted">Manage BRIX AI, the website and the blog.</p>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('/admin/login')) ?>" class="stack">
  <?= csrf_field() ?>
  <div class="field">
    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input id="password" type="password" name="password" autocomplete="current-password" required>
  </div>
  <button class="btn btn-primary btn-block" type="submit">Sign in</button>
</form>
<a class="login-back" href="<?= e(url('/')) ?>">← Back to the website</a>
