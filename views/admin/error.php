<section class="card empty-state">
  <h2><?= (int) ($status ?? 404) === 404 ? 'Not found' : 'Not allowed' ?></h2>
  <p class="muted"><?= e($message ?: 'That page does not exist.') ?></p>
  <a class="btn" href="<?= e(url('/admin')) ?>">Back to the dashboard</a>
</section>
