<?php
/**
 * @var array $conversation
 * @var array $messages
 * @var array $clicks
 */
?>
<div class="page-head">
  <a class="back-link" href="<?= e(url('/admin/chats')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?>Conversations</a>
  <?php if (is_admin_role()): ?>
  <form method="post" action="<?= e(url('/admin/chats/' . $conversation['id'] . '/delete')) ?>" data-confirm="Delete this conversation?">
    <?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit"><?= icon('trash', 'icon icon-sm') ?>Delete</button>
  </form>
  <?php endif; ?>
</div>

<div class="form-layout">
  <div class="form-main">
    <section class="card transcript">
      <?php foreach ($messages as $m): ?>
        <div class="turn turn-<?= $m['role'] === 'user' ? 'user' : 'ai' ?>">
          <div class="turn-head">
            <strong><?= $m['role'] === 'user' ? 'Visitor' : e(setting('ai_assistant_name')) ?></strong>
            <span class="meta"><?= e(format_date($m['created_at'], 'M j, g:i:s A')) ?></span>
            <?php if ($m['is_off_topic']): ?><span class="badge badge-amber">Off-topic</span><?php endif; ?>
            <?php if ($m['error']): ?><span class="badge badge-red" title="<?= e($m['error']) ?>">Error</span><?php endif; ?>
          </div>
          <div class="turn-text"><?= nl2br(e($m['content'])) ?></div>
          <?php if ($m['role'] === 'assistant'): ?>
            <div class="turn-meta">
              <?php if ($m['product_name']): ?><span>Recommended: <strong><?= e($m['product_name']) ?></strong></span><?php endif; ?>
              <?php foreach (json_decode((string) $m['sources'], true) ?: [] as $source): ?>
                <a class="badge badge-violet" href="<?= e(url('/admin/kb/' . (int) $source['id'])) ?>"><?= e(str_limit($source['title'], 50)) ?></a>
              <?php endforeach; ?>
              <span class="meta">
                <?= e($m['provider'] ?: '') ?><?= $m['model'] ? ' · ' . e($m['model']) : '' ?>
                <?= $m['latency_ms'] ? ' · ' . number_format($m['latency_ms'] / 1000, 1) . 's' : '' ?>
                <?= $m['output_tokens'] ? ' · ' . number_format((int) $m['input_tokens']) . ' in / ' . number_format((int) $m['output_tokens']) . ' out' : '' ?>
                <?= $m['cache_read_tokens'] ? ' · ' . number_format((int) $m['cache_read_tokens']) . ' cached' : '' ?>
              </span>
            </div>
            <?php if ($m['error']): ?><p class="field-error"><?= e($m['error']) ?></p><?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  </div>
  <aside class="form-side">
    <section class="card">
      <div class="card-head"><h2>Details</h2></div>
      <dl class="details">
        <dt>Started</dt><dd><?= e(format_date($conversation['created_at'], 'M j, Y g:i A')) ?></dd>
        <dt>Last message</dt><dd><?= e(time_ago($conversation['last_message_at'])) ?></dd>
        <dt>Messages</dt><dd><?= (int) $conversation['message_count'] ?></dd>
        <dt>Device</dt><dd class="small"><?= e(str_limit((string) $conversation['user_agent'], 120)) ?></dd>
        <dt>Visitor</dt><dd class="mono small"><?= e(substr((string) $conversation['ip_hash'], 0, 12)) ?>…</dd>
      </dl>
    </section>
    <section class="card">
      <div class="card-head"><h2>Install clicks</h2></div>
      <?php if ($clicks): ?>
        <ul class="list compact">
          <?php foreach ($clicks as $click): ?>
            <li><span class="list-main"><?= e($click['product_name'] ?: 'General install link') ?></span><span class="list-meta"><?= e(time_ago($click['created_at'])) ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted small">The visitor did not click an install button from this chat.</p>
      <?php endif; ?>
    </section>
  </aside>
</div>
