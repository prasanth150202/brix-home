<?php
/**
 * @var array $posts
 * @var array $pager
 * @var string $status
 * @var int $categoryId
 * @var string $search
 * @var array $categories
 */
$tabs = ['' => 'All', 'published' => 'Published', 'scheduled' => 'Scheduled', 'draft' => 'Drafts'];
?>
<div class="page-head">
  <div class="tabs">
    <?php foreach ($tabs as $key => $label): ?>
      <a class="<?= $status === $key ? 'is-active' : '' ?>" href="<?= e(url('/admin/posts') . ($key ? '?status=' . $key : '')) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <a class="btn btn-primary" href="<?= e(url('/admin/posts/new')) ?>"><?= icon('plus', 'icon icon-sm') ?>New post</a>
</div>

<form class="toolbar" method="get">
  <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
  <select name="category" onchange="this.form.submit()" aria-label="Filter by category">
    <option value="">All categories</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <div class="search"><?= icon('search', 'icon icon-sm') ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search titles"></div>
</form>

<?php if ($posts): ?>
<div class="card card-flush">
  <table class="table">
    <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Date</th><th>Views</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($posts as $post): ?>
      <tr>
        <td class="with-thumb">
          <span class="thumb"><?php if ($post['featured_image']): ?><img src="<?= e(media_url($post['featured_image'])) ?>" alt=""><?php else: ?><?= icon('newspaper', 'icon icon-sm') ?><?php endif; ?></span>
          <span><a class="strong" href="<?= e(url('/admin/posts/' . $post['id'])) ?>"><?= e($post['title']) ?></a><?php if ($post['is_featured']): ?> <span class="badge badge-violet">Featured</span><?php endif; ?>
          <span class="meta block">/blog/<?= e($post['slug']) ?></span></span>
        </td>
        <td><?= e($post['category_name'] ?: '—') ?></td>
        <td><?= status_badge($post['status'], $post['published_at']) ?></td>
        <td class="nowrap meta"><?= e(format_date($post['published_at'] ?: $post['updated_at'])) ?></td>
        <td class="meta"><?= number_format((int) $post['views']) ?></td>
        <td class="actions"><a class="btn btn-sm" href="<?= e(url('/admin/posts/' . $post['id'])) ?>">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php partial('admin/partials/pagination', ['pager' => $pager]); ?>
<?php else: ?>
<div class="card empty-state">
  <h2>No posts<?= $status || $search || $categoryId ? ' match' : ' yet' ?></h2>
  <p class="muted">Write guides and case studies. Published posts appear on /blog and in the homepage blog section.</p>
  <a class="btn btn-primary" href="<?= e(url('/admin/posts/new')) ?>">Write a post</a>
</div>
<?php endif; ?>
