<?php
use App\Kb\KnowledgeBase;

/**
 * @var ?array $doc
 * @var array $errors
 * @var array $products
 * @var array $chunks
 */
$isNew = $doc === null;
$productOptions = ['' => 'None'] + array_column($products, 'name', 'id');
$mode = (string) field_value('mode', 'file');
?>
<div class="page-head">
  <a class="back-link" href="<?= e(url('/admin/kb')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?>Knowledge base</a>
  <?php if (!$isNew): ?>
  <div class="page-actions">
    <a class="btn" href="<?= e(url('/admin/kb/' . $doc['id'] . '/download')) ?>"><?= icon('download', 'icon icon-sm') ?>Download</a>
    <form method="post" action="<?= e(url('/admin/kb/' . $doc['id'] . '/reindex')) ?>"><?= csrf_field() ?><button class="btn" type="submit"><?= icon('refresh', 'icon icon-sm') ?>Re-index</button></form>
    <form method="post" action="<?= e(url('/admin/kb/' . $doc['id'] . '/delete')) ?>" data-confirm="Delete this document? BRIX AI will stop using it."><?= csrf_field() ?><button class="btn btn-danger" type="submit"><?= icon('trash', 'icon icon-sm') ?>Delete</button></form>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($errors['document'])): ?><div class="alert alert-error"><?= e($errors['document']) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-layout">
  <?= csrf_field() ?>
  <div class="form-main">
    <section class="card">
      <?php if ($isNew): ?>
        <div class="card-head"><h2>Document</h2></div>
        <div class="segmented" data-tabs>
          <label><input type="radio" name="mode" value="file" <?= $mode === 'file' ? 'checked' : '' ?>><span><?= icon('upload', 'icon icon-sm') ?>Upload a file</span></label>
          <label><input type="radio" name="mode" value="text" <?= $mode === 'text' ? 'checked' : '' ?>><span><?= icon('pen', 'icon icon-sm') ?>Paste text</span></label>
        </div>
        <div data-tab-panel="file" <?= $mode === 'file' ? '' : 'hidden' ?>>
          <label class="dropzone" data-dropzone>
            <input type="file" name="document" accept=".pdf,.docx,.txt,.md,.markdown,.html,.htm">
            <?= icon('upload') ?>
            <strong>Drop a file here or click to choose</strong>
            <span class="muted">PDF, Word (.docx), Markdown, text or HTML · up to <?= human_filesize(DOCUMENT_MAX_BYTES) ?></span>
            <span class="dropzone-file" data-dropzone-file></span>
          </label>
          <p class="field-help">Scanned PDFs have no text to read. Export them with selectable text, or paste the text instead.</p>
        </div>
        <div data-tab-panel="text" <?= $mode === 'text' ? '' : 'hidden' ?>>
          <?= form_field('textarea', 'content', 'Document text', '', ['rows' => 16, 'help' => 'Markdown headings (## Heading) help split the text into sections.']) ?>
        </div>
      <?php else: ?>
        <div class="card-head"><h2>Text BRIX AI reads</h2><span class="meta"><?= number_format((int) $doc['char_count']) ?> characters · <?= (int) $doc['chunk_count'] ?> passages</span></div>
        <?= form_field('code', 'content', 'Extracted text', $doc['content'], ['rows' => 18, 'help' => 'Fix extraction mistakes here. Saving re-indexes the document.']) ?>
        <div class="field">
          <label for="replace">Replace with a new file</label>
          <input id="replace" type="file" name="document" accept=".pdf,.docx,.txt,.md,.markdown,.html,.htm">
          <p class="field-help">Current file: <?= $doc['original_name'] ? e($doc['original_name']) : 'none (pasted text)' ?></p>
        </div>
      <?php endif; ?>
    </section>

    <?php if (!$isNew && $chunks): ?>
    <section class="card">
      <div class="card-head"><h2>Passages</h2><span class="meta">What retrieval searches and hands to the AI</span></div>
      <div class="chunks">
        <?php foreach ($chunks as $chunk): ?>
          <details class="chunk">
            <summary><span class="chunk-num">#<?= (int) $chunk['chunk_index'] + 1 ?></span><span class="chunk-title"><?= e($chunk['heading'] ?: str_limit($chunk['content'], 80)) ?></span><span class="meta"><?= (int) $chunk['char_count'] ?> chars</span></summary>
            <pre><?= e($chunk['content']) ?></pre>
          </details>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>

  <aside class="form-side">
    <section class="card">
      <div class="card-head"><h2>Details</h2></div>
      <?= form_field('text', 'title', 'Title', $doc['title'] ?? '', ['error' => $errors['title'] ?? null, 'help' => $isNew ? 'Leave empty to use the first heading or the file name.' : null]) ?>
      <?= form_field('select', 'doc_type', 'Type', $doc['doc_type'] ?? 'case_study', ['options' => KnowledgeBase::DOC_TYPES]) ?>
      <?= form_field('select', 'product_id', 'Related product', $doc['product_id'] ?? '', ['options' => $productOptions, 'help' => 'Helps BRIX AI pick what to recommend.']) ?>
      <?= form_field('checkbox', 'is_active', 'BRIX AI can use this document', $doc['is_active'] ?? 1) ?>
    </section>

    <section class="card">
      <div class="card-head"><h2>Case study card</h2></div>
      <p class="muted small">Shown under chat answers that use this document, and on the homepage if featured.</p>
      <?= form_field('text', 'brand', 'Brand or store', $doc['brand'] ?? '', ['placeholder' => 'A skincare D2C brand']) ?>
      <?= form_field('text', 'industry', 'Industry', $doc['industry'] ?? '', ['placeholder' => 'Skincare']) ?>
      <div class="field-row">
        <?= form_field('text', 'result_value', 'Headline result', $doc['result_value'] ?? '', ['placeholder' => '+41%']) ?>
        <?= form_field('text', 'result_label', 'Result label', $doc['result_label'] ?? '', ['placeholder' => 'AOV in six weeks']) ?>
      </div>
      <?= form_field('textarea', 'summary', 'Summary', $doc['summary'] ?? '', ['rows' => 3, 'help' => 'One or two sentences. BRIX AI sees the summary of every case study.']) ?>
      <?= form_field('text', 'public_url', 'Link to the full story', $doc['public_url'] ?? '', ['placeholder' => '/blog/case-study-…']) ?>
      <?= form_field('checkbox', 'is_featured', 'Feature on the homepage', $doc['is_featured'] ?? 0) ?>
      <?= form_field('number', 'sort_order', 'Order', $doc['sort_order'] ?? 0) ?>
    </section>

    <button class="btn btn-primary btn-block" type="submit"><?= $isNew ? 'Upload and index' : 'Save document' ?></button>
  </aside>
</form>
