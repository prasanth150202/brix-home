<?php
/**
 * @var array $stats
 * @var array $recommended
 * @var array $clicks
 * @var array $recent
 * @var string $provider
 * @var bool $hasKey
 */
$questions = max(1, $stats['questions']);
?>
<?php if (!$hasKey): ?>
  <div class="alert alert-warning">
    <strong>BRIX AI is in demo mode.</strong> No <?= ['openai' => 'OpenAI', 'openrouter' => 'OpenRouter'][$provider] ?? 'Anthropic' ?> API key is saved, so answers are stitched together from raw excerpts.
    <?php if (is_admin_role()): ?><a href="<?= e(url('/admin/ai')) ?>">Add a key in AI settings</a>.<?php endif; ?>
  </div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card"><span>Chats (7 days)</span><strong><?= number_format($stats['chats_week']) ?></strong><small><?= number_format($stats['questions']) ?> questions</small></div>
  <div class="stat-card"><span>Install clicks (7 days)</span><strong><?= number_format($stats['clicks_week']) ?></strong><small>from the site and chat</small></div>
  <div class="stat-card"><span>Off-topic questions</span><strong><?= number_format($stats['off_topic']) ?></strong><small><?= round($stats['off_topic'] / $questions * 100) ?>% of questions</small></div>
  <div class="stat-card"><span>Knowledge base</span><strong><?= number_format($stats['documents']) ?></strong><small><?= number_format($stats['chunks']) ?> passages indexed</small></div>
</div>

<div class="grid-2">
  <section class="card">
    <div class="card-head"><h2>Recent conversations</h2><a class="btn btn-sm" href="<?= e(url('/admin/chats')) ?>">View all</a></div>
    <?php if ($recent): ?>
      <ul class="list">
        <?php foreach ($recent as $row): ?>
          <li>
            <a href="<?= e(url('/admin/chats/' . $row['id'])) ?>">
              <span class="list-main"><?= e(str_limit((string) $row['first_question'], 90)) ?></span>
              <span class="list-meta"><?= e(time_ago($row['created_at'])) ?> · <?= (int) $row['message_count'] ?> messages<?= $row['product_name'] ? ' · ' . e($row['product_name']) : '' ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="empty">No conversations yet. <a href="<?= e(url('/?chat=1')) ?>" target="_blank" rel="noopener">Ask BRIX AI something</a> to try it.</p>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head"><h2>What BRIX AI recommends (7 days)</h2></div>
    <?php if ($recommended): ?>
      <?php $max = max(array_column($recommended, 'times')); ?>
      <ul class="bars">
        <?php foreach ($recommended as $row): ?>
          <li><span><?= e($row['name']) ?></span><span class="bar"><i style="width: <?= round($row['times'] / $max * 100) ?>%"></i></span><strong><?= (int) $row['times'] ?></strong></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="empty">Recommendations appear here once visitors start chatting.</p>
    <?php endif; ?>

    <div class="card-head card-head-sub"><h2>Install clicks by product (7 days)</h2></div>
    <?php if ($clicks): ?>
      <?php $max = max(array_column($clicks, 'clicks')); ?>
      <ul class="bars bars-green">
        <?php foreach ($clicks as $row): ?>
          <li><span><?= e($row['name']) ?></span><span class="bar"><i style="width: <?= round($row['clicks'] / $max * 100) ?>%"></i></span><strong><?= (int) $row['clicks'] ?></strong></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="empty">No install clicks yet.</p>
    <?php endif; ?>
  </section>
</div>

<section class="card">
  <div class="card-head"><h2>Quick actions</h2></div>
  <div class="quick-actions">
    <a href="<?= e(url('/admin/kb/new')) ?>"><?= icon('upload') ?><span><strong>Upload a case study</strong><small>PDF, Word, Markdown or text</small></span></a>
    <a href="<?= e(url('/admin/posts/new')) ?>"><?= icon('pen') ?><span><strong>Write a blog post</strong><small><?= (int) $stats['posts'] ?> published</small></span></a>
    <a href="<?= e(url('/admin/pages/new')) ?>"><?= icon('file') ?><span><strong>Create a page</strong><small><?= (int) $stats['pages'] ?> published</small></span></a>
    <a href="<?= e(url('/admin/homepage')) ?>"><?= icon('layout') ?><span><strong>Edit the homepage</strong><small>Hero, sections and pricing</small></span></a>
  </div>
</section>
