<?php
use App\Kb\KnowledgeBase;

/**
 * @var array $documents
 * @var array $counts
 * @var string $type
 * @var string $search
 */
?>
<div class="page-head">
  <div>
    <p class="muted">Documents BRIX AI answers from. Each one is split into short passages; the best-matching passages are given to the AI with every question.</p>
  </div>
  <div class="page-actions">
    <a class="btn" href="<?= e(url('/admin/kb/test')) ?>"><?= icon('search', 'icon icon-sm') ?>Test retrieval</a>
    <a class="btn btn-primary" href="<?= e(url('/admin/kb/new')) ?>"><?= icon('upload', 'icon icon-sm') ?>Add document</a>
  </div>
</div>

<div class="toolbar">
  <div class="tabs">
    <a class="<?= $type === '' ? 'is-active' : '' ?>" href="<?= e(url('/admin/kb')) ?>">All <span><?= array_sum($counts) ?></span></a>
    <?php foreach (KnowledgeBase::DOC_TYPES as $key => $label): ?>
      <?php if (!empty($counts[$key])): ?>
        <a class="<?= $type === $key ? 'is-active' : '' ?>" href="<?= e(url('/admin/kb?type=' . $key)) ?>"><?= e($label) ?> <span><?= (int) $counts[$key] ?></span></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <form class="search" method="get">
    <?php if ($type): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
    <?= icon('search', 'icon icon-sm') ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search documents">
  </form>
</div>

<?php if ($documents): ?>
<div class="card card-flush">
  <table class="table">
    <thead><tr><th>Document</th><th>Type</th><th>Result</th><th>Passages</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($documents as $doc): ?>
      <tr class="<?= $doc['is_active'] ? '' : 'is-muted' ?>">
        <td>
          <a class="strong" href="<?= e(url('/admin/kb/' . $doc['id'])) ?>"><?= e($doc['title']) ?></a>
          <div class="meta">
            <?= $doc['source'] === 'upload' ? e($doc['original_name']) . ($doc['file_size'] ? ' · ' . human_filesize((int) $doc['file_size']) : '') : 'Pasted text' ?>
            <?= $doc['product_name'] ? ' · ' . e($doc['product_name']) : '' ?>
            <?= $doc['is_featured'] ? ' · <span class="badge badge-violet">On homepage</span>' : '' ?>
          </div>
        </td>
        <td><?= e(KnowledgeBase::DOC_TYPES[$doc['doc_type']] ?? $doc['doc_type']) ?></td>
        <td><?= $doc['result_value'] ? '<strong class="text-green">' . e($doc['result_value']) . '</strong> <span class="meta">' . e(str_limit((string) $doc['result_label'], 40)) . '</span>' : '<span class="meta">—</span>' ?></td>
        <td><?= (int) $doc['chunk_count'] ?> <span class="meta">· <?= number_format((int) $doc['char_count']) ?> chars</span></td>
        <td><?= $doc['is_active'] ? status_badge($doc['status']) : '<span class="badge">Off</span>' ?></td>
        <td class="actions"><a class="btn btn-sm" href="<?= e(url('/admin/kb/' . $doc['id'])) ?>">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="card empty-state">
  <h2><?= $search !== '' ? 'No documents match' : 'No documents yet' ?></h2>
  <p class="muted">Upload case studies, product guides or FAQs. BRIX AI will answer from them.</p>
  <a class="btn btn-primary" href="<?= e(url('/admin/kb/new')) ?>">Add the first document</a>
</div>
<?php endif; ?>
