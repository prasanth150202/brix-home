<?php
/**
 * @var array $posts
 * @var array $pager
 * @var array $categories
 * @var string $search
 * @var string $heading
 * @var string $intro
 */
$activeCategory = $category['id'] ?? null;
?>
<section class="page-hero blog-hero">
  <div class="container">
    <p class="eyebrow"><a href="<?= e(url('/blog')) ?>">Blog</a></p>
    <h1 class="page-title" data-split><?= e($heading) ?></h1>
    <p class="page-lead"><?= e($intro) ?></p>
    <form class="blog-search" action="<?= e(url(request_path())) ?>" method="get" role="search">
      <?= icon('search', 'icon icon-sm') ?>
      <label class="sr-only" for="blogSearch">Search articles</label>
      <input id="blogSearch" type="search" name="q" value="<?= e($search) ?>" placeholder="Search articles">
    </form>
    <?php if ($categories): ?>
    <nav class="chip-row blog-cats" aria-label="Categories">
      <a class="chip <?= $activeCategory === null && empty($tag) ? 'is-active' : '' ?>" href="<?= e(url('/blog')) ?>">All</a>
      <?php foreach ($categories as $cat): ?>
        <a class="chip <?= (int) $activeCategory === (int) $cat['id'] ? 'is-active' : '' ?>" href="<?= e(category_url($cat)) ?>"><?= e($cat['name']) ?> <small><?= (int) $cat['post_count'] ?></small></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
  </div>
</section>

<div class="slab theme-light">
  <section class="section">
    <div class="container">
      <?php if ($search !== ''): ?>
        <p class="result-note"><?= (int) $pager['total'] ?> result<?= $pager['total'] === 1 ? '' : 's' ?> for “<?= e($search) ?>”. <a href="<?= e(url(request_path())) ?>">Clear search</a></p>
      <?php endif; ?>
      <?php if ($posts): ?>
        <div class="post-grid">
          <?php foreach ($posts as $i => $post): ?>
            <?php partial('site/partials/post-card', ['post' => $post, 'index' => $i]); ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-note">
          <h2>Nothing here yet</h2>
          <p>Try another search, or ask BRIX AI directly.</p>
          <a class="btn btn-primary" href="<?= e(url('/?chat=1')) ?>"><?= icon('sparkles', 'icon icon-sm') ?>Ask BRIX AI</a>
        </div>
      <?php endif; ?>

      <?php if ($pager['pages'] > 1): ?>
      <nav class="pagination" aria-label="Pages">
        <?php if ($pager['page'] > 1): ?><a href="<?= e(query_with(['page' => $pager['page'] - 1])) ?>" rel="prev"><?= icon('arrow-left', 'icon icon-sm') ?>Newer</a><?php endif; ?>
        <span>Page <?= (int) $pager['page'] ?> of <?= (int) $pager['pages'] ?></span>
        <?php if ($pager['page'] < $pager['pages']): ?><a href="<?= e(query_with(['page' => $pager['page'] + 1])) ?>" rel="next">Older<?= icon('arrow-right', 'icon icon-sm') ?></a><?php endif; ?>
      </nav>
      <?php endif; ?>
    </div>
  </section>
</div>
