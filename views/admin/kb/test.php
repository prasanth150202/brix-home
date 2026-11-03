<?php
use App\Kb\KnowledgeBase;

/**
 * @var string $question
 * @var string $expanded
 * @var array $results
 */
?>
<p class="muted page-intro">Type a question a merchant might ask to see which passages BRIX AI would read before answering. If the right document doesn't show up, add a synonym in AI settings or improve the document's title and summary.</p>

<form class="card search-card" method="get">
  <div class="search-big">
    <?= icon('search') ?>
    <input type="search" name="q" value="<?= e($question) ?>" placeholder="How do I increase my AOV without more ad spend?" autofocus>
    <button class="btn btn-primary" type="submit">Search</button>
  </div>
  <?php if ($question !== '' && $expanded !== $question): ?>
    <p class="meta">Searched for: <?= e($expanded) ?></p>
  <?php endif; ?>
</form>

<?php if ($question !== ''): ?>
  <?php if ($results): ?>
    <?php foreach ($results as $i => $r): ?>
      <section class="card result">
        <div class="result-head">
          <span class="result-rank">#<?= $i + 1 ?></span>
          <div>
            <a class="strong" href="<?= e(url('/admin/kb/' . $r['document_id'])) ?>"><?= e($r['title']) ?></a>
            <div class="meta"><?= e(KnowledgeBase::DOC_TYPES[$r['doc_type']] ?? $r['doc_type']) ?><?= $r['heading'] ? ' · ' . e($r['heading']) : '' ?></div>
          </div>
          <span class="badge badge-violet">score <?= e((string) $r['score']) ?></span>
        </div>
        <pre class="result-text"><?= e($r['content']) ?></pre>
      </section>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="card empty-state">
      <h2>Nothing matched</h2>
      <p class="muted">BRIX AI would treat this as a question its documents can't answer: it gives general advice or the off-topic reply.</p>
    </div>
  <?php endif; ?>
<?php endif; ?>
