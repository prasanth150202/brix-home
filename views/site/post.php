<?php
/**
 * @var array $post
 * @var array $tags
 * @var array $related
 */
$shareUrl = absolute_url('/blog/' . $post['slug']);
$askQuestion = 'I just read "' . $post['title'] . '". How do I apply this to my Shopify store?';
?>
<?php if ($post['status'] !== 'published' || strtotime((string) $post['published_at']) > time()): ?>
  <div class="preview-bar">Preview. This post is not public yet. <a href="<?= e(url('/admin/posts/' . $post['id'])) ?>">Edit post</a></div>
<?php endif; ?>

<article class="article">
  <header class="article-head">
    <div class="container narrow">
      <div class="post-meta">
        <a href="<?= e(url('/blog')) ?>"><?= icon('arrow-left', 'icon icon-xs') ?>Blog</a>
        <?php if ($post['category_name']): ?><a class="post-cat" href="<?= e(url('/blog/category/' . $post['category_slug'])) ?>"><?= e($post['category_name']) ?></a><?php endif; ?>
      </div>
      <h1 class="article-title" data-split><?= e($post['title']) ?></h1>
      <?php if ($post['excerpt']): ?><p class="page-lead"><?= e($post['excerpt']) ?></p><?php endif; ?>
      <div class="article-byline">
        <span><?= e($post['author_name'] ?: setting('site_name') . ' team') ?></span>
        <span><?= e(format_date($post['published_at'], 'F j, Y')) ?></span>
        <span><?= (int) $post['reading_minutes'] ?> min read</span>
      </div>
    </div>
  </header>

  <div class="slab theme-light">
    <div class="article-sheet">
      <?php if ($post['featured_image']): ?>
      <div class="container article-cover">
        <img src="<?= e(media_url($post['featured_image'])) ?>" alt="<?= e($post['featured_image_alt'] ?: '') ?>">
      </div>
      <?php endif; ?>

      <div class="container narrow">
        <div class="prose article-body"><?= $post['content'] ?></div>

        <?php if ($tags): ?>
        <div class="chip-row article-tags">
          <?php foreach ($tags as $tag): ?><a class="chip" href="<?= e(tag_url($tag)) ?>">#<?= e($tag['name']) ?></a><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="share-row">
          <span>Share</span>
          <a href="https://twitter.com/intent/tweet?url=<?= rawurlencode($shareUrl) ?>&text=<?= rawurlencode($post['title']) ?>" target="_blank" rel="noopener" aria-label="Share on X"><?= icon('x-social') ?></a>
          <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= rawurlencode($shareUrl) ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><?= icon('linkedin') ?></a>
          <button type="button" data-copy="<?= e($shareUrl) ?>" aria-label="Copy link"><?= icon('link') ?></button>
        </div>

        <aside class="ask-box">
          <span class="ask-mark"><img src="<?= e(media_url((string) setting('site_mark'))) ?>" alt="" width="22" height="24"></span>
          <div>
            <h2>Want this for your store?</h2>
            <p>Ask BRIX AI how to apply this article, with numbers from real BRIX case studies.</p>
          </div>
          <a class="btn btn-primary" href="<?= e(url('/?ask=' . rawurlencode($askQuestion))) ?>" data-magnetic><?= icon('sparkles', 'icon icon-sm') ?>Ask BRIX AI</a>
        </aside>
      </div>

      <?php if ($related): ?>
      <section class="section section-tight">
        <div class="container">
          <h2 class="section-title section-title-sm">Keep reading</h2>
          <div class="post-grid">
            <?php foreach ($related as $i => $item): ?>
              <?php partial('site/partials/post-card', ['post' => $item, 'index' => $i]); ?>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
      <?php endif; ?>
    </div>
  </div>
</article>
