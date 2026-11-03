<?php
/**
 * @var array $def
 * @var array $rows
 */
$name = $def['name'];
$hasActive = isset($def['fields']['is_active']);
?>
<div class="page-head">
  <p class="muted"><?= e($def['intro']) ?></p>
  <a class="btn btn-primary" href="<?= e(url("/admin/r/$name/new")) ?>"><?= icon('plus', 'icon icon-sm') ?>Add <?= e($def['singular']) ?></a>
</div>

<?php if ($rows): ?>
<div class="card card-flush">
  <table class="table">
    <thead>
      <tr>
        <?php foreach ($def['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
        <?php if ($hasActive): ?><th>Visible</th><?php endif; ?>
        <th>Order</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
      <tr class="<?= $hasActive && !$row['is_active'] ? 'is-muted' : '' ?>">
        <?php $first = true; foreach ($def['columns'] as $col => $label): ?>
          <td>
            <?php $value = (string) ($row[$col] ?? ''); ?>
            <?php if ($first): ?>
              <a class="strong" href="<?= e(url("/admin/r/$name/{$row['id']}")) ?>"><?= e(str_limit($value, 70)) ?></a>
            <?php elseif (in_array($col, ['price_monthly', 'price_yearly'], true)): ?>
              <?= $value === '' ? '<span class="meta">—</span>' : e(setting('currency_symbol') . money($value)) ?>
            <?php else: ?>
              <span class="<?= $value === '' ? 'meta' : '' ?>"><?= $value === '' ? '—' : e(str_limit($value, 70)) ?></span>
            <?php endif; ?>
          </td>
        <?php $first = false; endforeach; ?>
        <?php if ($hasActive): ?>
          <td>
            <form method="post" action="<?= e(url("/admin/r/$name/{$row['id']}/toggle")) ?>"><?= csrf_field() ?>
              <button type="submit" class="toggle-pill <?= $row['is_active'] ? 'is-on' : '' ?>"><?= $row['is_active'] ? 'On' : 'Off' ?></button>
            </form>
          </td>
        <?php endif; ?>
        <td class="meta"><?= (int) ($row['sort_order'] ?? 0) ?></td>
        <td class="actions">
          <a class="btn btn-sm" href="<?= e(url("/admin/r/$name/{$row['id']}")) ?>">Edit</a>
          <form method="post" action="<?= e(url("/admin/r/$name/{$row['id']}/delete")) ?>" data-confirm="Delete this <?= e($def['singular']) ?>?"><?= csrf_field() ?><button class="btn btn-sm btn-quiet" type="submit" aria-label="Delete"><?= icon('trash', 'icon icon-sm') ?></button></form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="card empty-state">
  <h2>Nothing here yet</h2>
  <a class="btn btn-primary" href="<?= e(url("/admin/r/$name/new")) ?>">Add the first <?= e($def['singular']) ?></a>
</div>
<?php endif; ?>
