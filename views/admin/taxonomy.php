<?php
/**
 * @var array $categories
 * @var array $tags
 * @var ?array $editing
 * @var array $errors
 */
?>
<div class="form-layout">
  <div class="form-main">
    <section class="card card-flush">
      <div class="card-head padded"><h2>Categories</h2></div>
      <?php if ($categories): ?>
      <table class="table">
        <thead><tr><th>Name</th><th>URL</th><th>Posts</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
          <tr>
            <td><strong><?= e($cat['name']) ?></strong><?php if ($cat['description']): ?><div class="meta"><?= e(str_limit($cat['description'], 80)) ?></div><?php endif; ?></td>
            <td><a class="mono small" href="<?= e(url('/blog/category/' . $cat['slug'])) ?>" target="_blank" rel="noopener">/blog/category/<?= e($cat['slug']) ?></a></td>
            <td><?= (int) $cat['post_count'] ?></td>
            <td class="actions">
              <a class="btn btn-sm" href="<?= e(url('/admin/categories?edit=' . $cat['id'])) ?>">Edit</a>
              <form method="post" action="<?= e(url('/admin/categories/' . $cat['id'] . '/delete')) ?>" data-confirm="Delete the category “<?= e($cat['name']) ?>”? Its posts become uncategorised."><?= csrf_field() ?><button class="btn btn-sm btn-quiet" type="submit" aria-label="Delete"><?= icon('trash', 'icon icon-sm') ?></button></form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><p class="empty padded">No categories yet.</p><?php endif; ?>
    </section>

    <section class="card">
      <div class="card-head"><h2>Tags</h2><span class="meta">Tags are created from the post editor</span></div>
      <?php if ($tags): ?>
        <div class="tag-cloud">
          <?php foreach ($tags as $tag): ?>
            <span class="tag-pill">
              <a href="<?= e(url('/blog/tag/' . $tag['slug'])) ?>" target="_blank" rel="noopener">#<?= e($tag['name']) ?></a><small><?= (int) $tag['post_count'] ?></small>
              <form method="post" action="<?= e(url('/admin/tags/' . $tag['id'] . '/delete')) ?>" data-confirm="Delete the tag #<?= e($tag['name']) ?>?"><?= csrf_field() ?><button type="submit" aria-label="Delete tag"><?= icon('x', 'icon icon-xs') ?></button></form>
            </span>
          <?php endforeach; ?>
        </div>
      <?php else: ?><p class="empty">No tags yet.</p><?php endif; ?>
    </section>
  </div>

  <aside class="form-side">
    <form method="post" class="card" action="<?= e(url('/admin/categories')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
      <div class="card-head"><h2><?= !empty($editing['id']) ? 'Edit category' : 'New category' ?></h2></div>
      <?= form_field('text', 'name', 'Name', $editing['name'] ?? '', ['required' => true, 'error' => $errors['name'] ?? null, 'attrs' => ['data-slug-source' => 'slug']]) ?>
      <div class="field<?= isset($errors['slug']) ? ' has-error' : '' ?>">
        <label for="f-slug">Slug</label>
        <input id="f-slug" name="slug" value="<?= e($editing['slug'] ?? '') ?>" data-slug-target <?= !empty($editing['id']) ? 'data-slug-locked' : '' ?>>
        <?php if (isset($errors['slug'])): ?><p class="field-error"><?= e($errors['slug']) ?></p><?php endif; ?>
      </div>
      <?= form_field('textarea', 'description', 'Description', $editing['description'] ?? '', ['rows' => 3, 'help' => 'Shown at the top of the category page.']) ?>
      <?= form_field('number', 'sort_order', 'Order', $editing['sort_order'] ?? 0) ?>
      <button class="btn btn-primary btn-block" type="submit">Save category</button>
      <?php if (!empty($editing['id'])): ?><a class="btn btn-block btn-quiet" href="<?= e(url('/admin/categories')) ?>">Cancel</a><?php endif; ?>
    </form>
  </aside>
</div>
