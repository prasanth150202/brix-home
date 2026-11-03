<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sign in · <?= e(setting('site_name')) ?> admin</title>
<link rel="icon" href="<?= e(media_url((string) setting('site_favicon'))) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="login-page">
  <main class="login-card">
    <img class="login-logo" src="<?= e(url('assets/img/brix-logo-dark.png')) ?>" alt="<?= e(setting('site_name')) ?>" height="34">
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
  </main>
</body>
</html>
