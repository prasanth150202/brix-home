<?php
/**
 * Shell for the signed-out admin page (sign in).
 *
 * @var string $title
 * @var string $content
 */
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
<title><?= e($title) ?> · <?= e(setting('site_name')) ?> admin</title>
<link rel="icon" href="<?= e(media_url((string) setting('site_favicon'))) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="login-page">
  <main class="login-card">
    <img class="login-logo" src="<?= e(url('assets/img/brix-logo-dark.png')) ?>" alt="<?= e(setting('site_name')) ?>" height="34">
    <?php foreach (flashes() as $flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </main>
</body>
</html>
