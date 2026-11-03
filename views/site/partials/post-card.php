<?php
/** @var array $post */
$angle = crc32($post['slug']) % 360;
?>
<article class="post-card reveal" style="--r: <?= (int) (($index ?? 0) % 3) ?>">
  <a class="post-card-media" href="<?= e(post_url($post)) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($post['featured_image']): ?>
      <img src="<?= e(media_url($post['featured_image'])) ?>" alt="" loading="lazy">
    <?php else: ?>
      <span class="post-card-art" style="--a: <?= $angle ?>deg"><?= icon(($post['category_slug'] ?? '') === 'case-studies' ? 'trending-up' : 'file', 'icon') ?></span>
    <?php endif; ?>
  </a>
  <div class="post-card-body">
    <div class="post-meta">
      <?php if (!empty($post['category_name'])): ?>
        <a class="post-cat" href="<?= e(url('/blog/category/' . $post['category_slug'])) ?>"><?= e($post['category_name']) ?></a>
      <?php endif; ?>
      <span><?= e(format_date($post['published_at'])) ?></span>
      <span><?= (int) $post['reading_minutes'] ?> min read</span>
    </div>
    <h3><a href="<?= e(post_url($post)) ?>"><?= e($post['title']) ?></a></h3>
    <?php if ($post['excerpt']): ?><p><?= e(str_limit($post['excerpt'], 150)) ?></p><?php endif; ?>
  </div>
</article>
