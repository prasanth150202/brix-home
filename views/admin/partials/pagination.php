<?php /** @var array $pager */ ?>
<?php if ($pager['pages'] > 1): ?>
<nav class="pager" aria-label="Pages">
  <?php if ($pager['page'] > 1): ?><a class="btn btn-sm" href="<?= e(query_with(['page' => $pager['page'] - 1])) ?>">← Previous</a><?php endif; ?>
  <span class="meta">Page <?= (int) $pager['page'] ?> of <?= (int) $pager['pages'] ?> · <?= number_format($pager['total']) ?> total</span>
  <?php if ($pager['page'] < $pager['pages']): ?><a class="btn btn-sm" href="<?= e(query_with(['page' => $pager['page'] + 1])) ?>">Next →</a><?php endif; ?>
</nav>
<?php endif; ?>
