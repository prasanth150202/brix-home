<?php /** @var array $pages */ ?>
<div class="page-head">
  <p class="muted">Custom pages live at whatever URL you give them, like /about or /features/cart-upsell.</p>
  <a class="btn btn-primary" href="<?= e(url('/admin/pages/new')) ?>"><?= icon('plus', 'icon icon-sm') ?>New page</a>
</div>

<?php if ($pages): ?>
<div class="card card-flush">
  <table class="table">
    <thead><tr><th>Title</th><th>URL</th><th>Menus</th><th>Status</th><th>Updated</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($pages as $page): ?>
      <tr>
        <td><a class="strong" href="<?= e(url('/admin/pages/' . $page['id'])) ?>"><?= e($page['title']) ?></a><div class="meta"><?= e(PAGE_TEMPLATES[$page['template']] ?? $page['template']) ?></div></td>
        <td><a class="mono small" href="<?= e(url('/' . $page['slug'])) ?>" target="_blank" rel="noopener">/<?= e($page['slug']) ?></a></td>
        <td class="meta"><?= implode(', ', array_filter([$page['show_in_header'] ? 'Header' : null, $page['show_in_footer'] ? 'Footer' : null])) ?: '—' ?></td>
        <td><?= status_badge($page['status']) ?></td>
        <td class="nowrap meta"><?= e(time_ago($page['updated_at'] ?: $page['created_at'])) ?></td>
        <td class="actions"><a class="btn btn-sm" href="<?= e(url('/admin/pages/' . $page['id'])) ?>">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="card empty-state">
  <h2>No pages yet</h2>
  <p class="muted">Create About, Contact, Privacy Policy or landing pages and choose their URLs.</p>
  <a class="btn btn-primary" href="<?= e(url('/admin/pages/new')) ?>">Create a page</a>
</div>
<?php endif; ?>
