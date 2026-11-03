<?php
$isHome ??= false;
$home = $isHome ? '' : url('/');
?>
<header class="site-header" id="siteHeader">
  <div class="container header-row" data-dissolve>
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e(setting('site_name')) ?> home">
      <img src="<?= e(media_url((string) setting('site_logo'))) ?>" alt="<?= e(setting('site_name')) ?>" width="111" height="32">
    </a>
    <nav class="nav" id="siteNav" aria-label="Main">
      <a href="<?= e($home) ?>#products">Products</a>
      <a href="<?= e($home) ?>#results">Case studies</a>
      <a href="<?= e(url('/blog')) ?>">Blog</a>
      <?php foreach (nav_pages('header') as $navPage): ?>
        <a href="<?= e(page_url($navPage)) ?>"><?= e($navPage['title']) ?></a>
      <?php endforeach; ?>
      <a class="nav-cta" href="<?= e(url('/?chat=1')) ?>" data-open-chat><?= icon('sparkles', 'icon icon-sm') ?> Ask BRIX AI</a>
    </nav>
    <div class="header-actions">
      <a class="btn btn-ghost btn-sm hide-mobile" href="<?= e(url('/?chat=1')) ?>" data-open-chat><?= icon('sparkles', 'icon icon-sm') ?> Ask BRIX AI</a>
      <a class="btn btn-primary btn-sm" href="<?= e(setting('install_url')) ?>" target="_blank" rel="noopener" data-track="header">Install app</a>
      <button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false" aria-controls="siteNav"><?= icon('menu') ?></button>
    </div>
  </div>
  <span class="scroll-progress" id="scrollProgress" aria-hidden="true"></span>
</header>
