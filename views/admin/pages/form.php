<?php
/**
 * @var ?array $page
 * @var array $errors
 */
$isNew = $page === null;
$siteUrl = rtrim(absolute_url('/'), '/');
?>
<div class="page-head">
  <a class="back-link" href="<?= e(url('/admin/pages')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?>Pages</a>
  <?php if (!$isNew): ?>
  <div class="page-actions">
    <a class="btn" href="<?= e(url('/' . $page['slug'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 'icon icon-sm') ?><?= $page['status'] === 'published' ? 'View' : 'Preview' ?></a>
    <form method="post" action="<?= e(url('/admin/pages/' . $page['id'] . '/delete')) ?>" data-confirm="Delete this page? Its URL will stop working."><?= csrf_field() ?><button class="btn btn-danger" type="submit"><?= icon('trash', 'icon icon-sm') ?>Delete</button></form>
  </div>
  <?php endif; ?>
</div>

<form method="post" class="form-layout" data-seo-form>
  <?= csrf_field() ?>
  <div class="form-main">
    <section class="card">
      <?= form_field('text', 'title', 'Title', $page['title'] ?? '', ['required' => true, 'error' => $errors['title'] ?? null, 'attrs' => ['data-slug-source' => 'slug', 'class' => 'input-lg']]) ?>
      <div class="field<?= isset($errors['slug']) ? ' has-error' : '' ?>">
        <label for="f-slug">URL</label>
        <div class="input-prefix"><span><?= e($siteUrl) ?>/</span><input id="f-slug" name="slug" value="<?= e(field_value('slug', $page['slug'] ?? '')) ?>" placeholder="about-us" data-slug-target <?= $isNew ? '' : 'data-slug-locked' ?>></div>
        <p class="field-help">Lowercase words separated by dashes. Use slashes for sections, e.g. <code>legal/privacy</code>.</p>
        <?php if (isset($errors['slug'])): ?><p class="field-error"><?= e($errors['slug']) ?></p><?php endif; ?>
      </div>
      <div class="field field-wide">
        <label for="content">Content</label>
        <textarea id="content" name="content" class="richtext" rows="20"><?= e(field_value('content', $page['content'] ?? '')) ?></textarea>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><h2>Search engines and sharing</h2></div>
      <div class="serp" data-serp>
        <span class="serp-url"><?= e($siteUrl) ?>/<span data-serp-slug><?= e($page['slug'] ?? '') ?></span></span>
        <span class="serp-title" data-serp-title><?= e(($page['meta_title'] ?? '') ?: ($page['title'] ?? 'Page title')) ?></span>
        <span class="serp-desc" data-serp-desc><?= e($page['meta_description'] ?? 'Your page description appears here.') ?></span>
      </div>
      <?= form_field('text', 'meta_title', 'SEO title', $page['meta_title'] ?? '', ['count' => 60, 'help' => 'Defaults to the page title.', 'attrs' => ['data-serp-source' => 'title']]) ?>
      <?= form_field('textarea', 'meta_description', 'Meta description', $page['meta_description'] ?? '', ['rows' => 3, 'count' => 160, 'help' => 'Also shown under the page title.', 'attrs' => ['data-serp-source' => 'desc']]) ?>
      <?= form_field('image', 'og_image', 'Share image', $page['og_image'] ?? '') ?>
      <?= form_field('checkbox', 'noindex', 'Hide from search engines (noindex)', $page['noindex'] ?? 0) ?>
    </section>

    <section class="card">
      <div class="card-head"><h2>Custom CSS</h2></div>
      <?= form_field('code', 'custom_css', 'CSS for this page only', $page['custom_css'] ?? '', ['rows' => 6, 'placeholder' => '.page-blank .hero { padding: 120px 0; }']) ?>
    </section>
  </div>

  <aside class="form-side">
    <section class="card">
      <div class="card-head"><h2>Publish</h2></div>
      <?= form_field('select', 'status', 'Status', $page['status'] ?? 'draft', ['options' => ['draft' => 'Draft', 'published' => 'Published']]) ?>
      <?= form_field('select', 'template', 'Layout', $page['template'] ?? 'default', ['options' => PAGE_TEMPLATES]) ?>
      <button class="btn btn-primary btn-block" type="submit"><?= $isNew ? 'Create page' : 'Save page' ?></button>
      <?php if (!$isNew): ?><p class="meta center">Updated <?= e(time_ago($page['updated_at'] ?: $page['created_at'])) ?></p><?php endif; ?>
    </section>
    <section class="card">
      <div class="card-head"><h2>Menus</h2></div>
      <?= form_field('checkbox', 'show_in_header', 'Show in the header menu', $page['show_in_header'] ?? 0) ?>
      <?= form_field('checkbox', 'show_in_footer', 'Show in the footer', $page['show_in_footer'] ?? 0) ?>
      <?= form_field('number', 'menu_order', 'Menu order', $page['menu_order'] ?? 0, ['help' => 'Lower numbers come first.']) ?>
    </section>
  </aside>
</form>
