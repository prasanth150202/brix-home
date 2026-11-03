<?php
/**
 * @var array $conversations
 * @var array $pager
 * @var string $filter
 * @var string $search
 */
$filters = ['' => 'All', 'clicked' => 'Clicked install', 'offtopic' => 'Off-topic', 'errors' => 'Errors'];
?>
<div class="toolbar">
  <div class="tabs">
    <?php foreach ($filters as $key => $label): ?>
      <a class="<?= $filter === $key ? 'is-active' : '' ?>" href="<?= e(url('/admin/chats') . ($key ? '?filter=' . $key : '')) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <form class="search" method="get">
    <?php if ($filter): ?><input type="hidden" name="filter" value="<?= e($filter) ?>"><?php endif; ?>
    <?= icon('search', 'icon icon-sm') ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search messages">
  </form>
</div>

<?php if ($conversations): ?>
<div class="card card-flush">
  <table class="table">
    <thead><tr><th>First question</th><th>Messages</th><th>Recommended</th><th>Clicks</th><th>Started</th></tr></thead>
    <tbody>
      <?php foreach ($conversations as $c): ?>
      <tr>
        <td>
          <a class="strong" href="<?= e(url('/admin/chats/' . $c['id'])) ?>"><?= e(str_limit((string) $c['first_question'], 110)) ?></a>
          <?php if ($c['off_topic']): ?> <span class="badge badge-amber">Off-topic</span><?php endif; ?>
        </td>
        <td><?= (int) $c['message_count'] ?></td>
        <td><?= $c['product_name'] ? e($c['product_name']) : '<span class="meta">—</span>' ?></td>
        <td><?= $c['clicks'] ? '<span class="badge badge-green">' . (int) $c['clicks'] . '</span>' : '<span class="meta">0</span>' ?></td>
        <td class="nowrap"><?= e(time_ago($c['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php partial('admin/partials/pagination', ['pager' => $pager]); ?>
<?php else: ?>
<div class="card empty-state">
  <h2>No conversations<?= $filter || $search ? ' match' : ' yet' ?></h2>
  <p class="muted">Every question asked on the website shows up here, with the answer, the documents used and the product recommended.</p>
</div>
<?php endif; ?>
