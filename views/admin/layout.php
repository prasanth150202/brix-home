<?php
/** @var string $content */
$me = current_admin();
$path = request_path();
$isAdmin = is_admin_role();
$nav = [
    '' => [
        ['/admin', 'Dashboard', 'home', true],
    ],
    'AI assistant' => [
        ['/admin/kb', 'Knowledge base', 'database', true],
        ['/admin/kb/test', 'Test retrieval', 'search', true],
        ['/admin/chats', 'Conversations', 'message', true],
        ['/admin/r/suggestions', 'Question tags', 'tag', true],
        ['/admin/ai', 'AI settings', 'sliders', $isAdmin],
    ],
    'Website' => [
        ['/admin/homepage', 'Homepage', 'layout', true],
        ['/admin/r/products', 'Products', 'package', true],
        ['/admin/r/plans', 'Pricing plans', 'dollar', true],
        ['/admin/r/testimonials', 'Testimonials', 'quote', true],
        ['/admin/r/faqs', 'FAQs', 'help', true],
    ],
    'Content' => [
        ['/admin/pages', 'Pages', 'file', true],
        ['/admin/posts', 'Blog posts', 'newspaper', true],
        ['/admin/categories', 'Categories & tags', 'folder', true],
        ['/admin/media', 'Media library', 'image', true],
    ],
    'Settings' => [
        ['/admin/settings', 'Site settings', 'globe', $isAdmin],
        ['/admin/users', 'Users', 'users', $isAdmin],
        ['/admin/account', 'My account', 'user', true],
    ],
];
// The most specific nav link that matches the current page.
$active = '';
foreach ($nav as $links) {
    foreach ($links as [$href]) {
        if (($path === $href || str_starts_with($path, $href . '/')) && strlen($href) > strlen($active)) {
            $active = $href;
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($title ?? 'Admin') ?> · <?= e(setting('site_name')) ?> admin</title>
<link rel="icon" href="<?= e(media_url((string) setting('site_favicon'))) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body data-base="<?= e(base_url_path()) ?>">
<div class="admin">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-head">
      <a class="sidebar-brand" href="<?= e(url('/admin')) ?>">
        <img src="<?= e(url('assets/img/brix-logo-light.png')) ?>" alt="<?= e(setting('site_name')) ?>" height="24">
        <span>Admin</span>
      </a>
    </div>
    <nav class="sidebar-nav" aria-label="Admin">
      <?php foreach ($nav as $group => $links): ?>
        <?php $visible = array_filter($links, static fn ($l) => $l[3]); ?>
        <?php if (!$visible) continue; ?>
        <?php if ($group !== ''): ?><p class="nav-group"><?= e($group) ?></p><?php endif; ?>
        <?php foreach ($visible as [$href, $label, $iconName]): ?>
          <a href="<?= e(url($href)) ?>" class="<?= $active === $href ? 'is-active' : '' ?>"><?= icon($iconName, 'icon icon-sm') ?><?= e($label) ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon-sm') ?>View website</a>
      <form method="post" action="<?= e(url('/admin/logout')) ?>">
        <?= csrf_field() ?>
        <button type="submit"><?= icon('log-out', 'icon icon-sm') ?>Sign out</button>
      </form>
    </div>
  </aside>
  <div class="sidebar-scrim" data-sidebar-close></div>

  <div class="main">
    <header class="topbar">
      <button type="button" class="topbar-menu" data-sidebar-toggle aria-label="Menu"><?= icon('menu') ?></button>
      <h1 class="topbar-title"><?= e($title ?? 'Admin') ?></h1>
      <div class="topbar-user">
        <span class="avatar-sm"><?= e(mb_strtoupper(mb_substr($me['name'] ?? '?', 0, 1))) ?></span>
        <span class="topbar-name"><?= e($me['name'] ?? '') ?><small><?= e(ucfirst($me['role'] ?? '')) ?></small></span>
      </div>
    </header>

    <div class="content">
      <?php foreach (flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?><button type="button" class="alert-close" aria-label="Dismiss" data-dismiss><?= icon('x', 'icon icon-sm') ?></button></div>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
  </div>
</div>

<div class="modal" id="mediaModal" hidden>
  <div class="modal-box">
    <div class="modal-head"><h2>Media library</h2><button type="button" class="btn btn-sm btn-quiet" data-modal-close><?= icon('x', 'icon icon-sm') ?></button></div>
    <div class="media-grid modal-grid" id="mediaModalGrid"><p class="muted">Loading…</p></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@7.9.3/tinymce.min.js" referrerpolicy="origin" defer></script>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</body>
</html>
