<?php
/**
 * @var ?array $post
 * @var string $tags
 * @var array $errors
 * @var array $categories
 * @var array $allTags
 */
$isNew = $post === null;
$blogUrl = rtrim(absolute_url('/blog'), '/');
$publishedAt = $post['published_at'] ?? '';
$publishedValue = (string) field_value('published_at', $publishedAt ? date('Y-m-d\TH:i', strtotime($publishedAt)) : '');
$categoryOptions = ['' => 'Uncategorised'] + array_column($categories, 'name', 'id');
?>
<div class="page-head">
  <a class="back-link" href="<?= e(url('/admin/posts')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?>Blog posts</a>
  <?php if (!$isNew): ?>
  <div class="page-actions">
    <a class="btn" href="<?= e(url('/blog/' . $post['slug'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 'icon icon-sm') ?><?= $post['status'] === 'published' ? 'View' : 'Preview' ?></a>
    <form method="post" action="<?= e(url('/admin/posts/' . $post['id'] . '/delete')) ?>" data-confirm="Delete this post?"><?= csrf_field() ?><button class="btn btn-danger" type="submit"><?= icon('trash', 'icon icon-sm') ?>Delete</button></form>
  </div>
  <?php endif; ?>
</div>

<form method="post" class="form-layout" data-seo-form>
  <?= csrf_field() ?>
  <div class="form-main">
    <section class="card">
      <?= form_field('text', 'title', 'Title', $post['title'] ?? '', ['required' => true, 'error' => $errors['title'] ?? null, 'attrs' => ['data-slug-source' => 'slug', 'class' => 'input-lg']]) ?>
      <div class="field<?= isset($errors['slug']) ? ' has-error' : '' ?>">
        <label for="f-slug">URL</label>
        <div class="input-prefix"><span><?= e($blogUrl) ?>/</span><input id="f-slug" name="slug" value="<?= e(field_value('slug', $post['slug'] ?? '')) ?>" placeholder="my-post" data-slug-target <?= $isNew ? '' : 'data-slug-locked' ?>></div>
        <?php if (isset($errors['slug'])): ?><p class="field-error"><?= e($errors['slug']) ?></p><?php endif; ?>
      </div>
      <?= form_field('textarea', 'excerpt', 'Excerpt', $post['excerpt'] ?? '', ['rows' => 3, 'help' => 'Shown on blog cards and under the title.', 'count' => 200]) ?>
      <div class="field field-wide">
        <label for="content">Content</label>
        <textarea id="content" name="content" class="richtext" rows="24"><?= e(field_value('content', $post['content'] ?? '')) ?></textarea>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><h2>Search engines and sharing</h2></div>
      <div class="serp" data-serp>
        <span class="serp-url"><?= e($blogUrl) ?>/<span data-serp-slug><?= e($post['slug'] ?? '') ?></span></span>
        <span class="serp-title" data-serp-title><?= e(($post['meta_title'] ?? '') ?: ($post['title'] ?? 'Post title')) ?></span>
        <span class="serp-desc" data-serp-desc><?= e(($post['meta_description'] ?? '') ?: ($post['excerpt'] ?? 'Your post description appears here.')) ?></span>
      </div>
      <?= form_field('text', 'meta_title', 'SEO title', $post['meta_title'] ?? '', ['count' => 60, 'help' => 'Defaults to the post title.', 'attrs' => ['data-serp-source' => 'title']]) ?>
      <?= form_field('textarea', 'meta_description', 'Meta description', $post['meta_description'] ?? '', ['rows' => 3, 'count' => 160, 'help' => 'Defaults to the excerpt.', 'attrs' => ['data-serp-source' => 'desc']]) ?>
      <div class="field-row">
        <?= form_field('image', 'og_image', 'Share image', $post['og_image'] ?? '', ['help' => 'Defaults to the featured image.']) ?>
        <?= form_field('url', 'canonical_url', 'Canonical URL', $post['canonical_url'] ?? '', ['help' => 'Only if this post was first published elsewhere.']) ?>
      </div>
      <?= form_field('checkbox', 'noindex', 'Hide from search engines (noindex)', $post['noindex'] ?? 0) ?>
    </section>
  </div>

  <aside class="form-side">
    <section class="card">
      <div class="card-head"><h2>Publish</h2></div>
      <?= form_field('select', 'status', 'Status', $post['status'] ?? 'draft', ['options' => ['draft' => 'Draft', 'published' => 'Published']]) ?>
      <?= form_field('datetime-local', 'published_at', 'Publish date', $publishedValue, ['error' => $errors['published_at'] ?? null, 'help' => 'Set a future date to schedule the post.']) ?>
      <?= form_field('checkbox', 'is_featured', 'Feature this post', $post['is_featured'] ?? 0) ?>
      <button class="btn btn-primary btn-block" type="submit"><?= $isNew ? 'Save post' : 'Update post' ?></button>
      <?php if (!$isNew): ?><p class="meta center"><?= number_format((int) $post['views']) ?> views · <?= (int) $post['reading_minutes'] ?> min read</p><?php endif; ?>
    </section>
    <section class="card">
      <div class="card-head"><h2>Organise</h2></div>
      <?= form_field('select', 'category_id', 'Category', $post['category_id'] ?? '', ['options' => $categoryOptions, 'help' => '<a href="' . e(url('/admin/categories')) . '">Manage categories</a>']) ?>
      <?= form_field('text', 'tags', 'Tags', $tags, ['placeholder' => 'upsells, free shipping', 'help' => 'Separate tags with commas.', 'attrs' => ['list' => 'tag-list']]) ?>
      <datalist id="tag-list"><?php foreach ($allTags as $tag): ?><option value="<?= e($tag['name']) ?>"><?php endforeach; ?></datalist>
      <?= form_field('text', 'author_name', 'Author name', $post['author_name'] ?? '', ['placeholder' => setting('site_name') . ' team']) ?>
    </section>
    <section class="card">
      <div class="card-head"><h2>Featured image</h2></div>
      <?= form_field('image', 'featured_image', 'Image', $post['featured_image'] ?? '') ?>
      <?= form_field('text', 'featured_image_alt', 'Alt text', $post['featured_image_alt'] ?? '', ['help' => 'Describe the image for screen readers.']) ?>
    </section>
  </aside>
</form>
