<?php
/**
 * @var array $items
 * @var array $pager
 */
?>
<form method="post" enctype="multipart/form-data" class="card">
  <?= csrf_field() ?>
  <label class="dropzone dropzone-inline" data-dropzone>
    <input type="file" name="files[]" accept="image/png,image/jpeg,image/gif,image/webp" multiple data-autosubmit>
    <?= icon('upload') ?>
    <strong>Upload images</strong>
    <span class="muted">JPG, PNG, WebP or GIF · up to <?= human_filesize(IMAGE_MAX_BYTES) ?> each · select several at once</span>
  </label>
</form>

<?php if ($items): ?>
<div class="media-grid">
  <?php foreach ($items as $item): ?>
    <figure class="media-item">
      <a href="<?= e(media_url($item['path'])) ?>" target="_blank" rel="noopener"><img src="<?= e(media_url($item['path'])) ?>" alt="<?= e($item['alt'] ?? '') ?>" loading="lazy"></a>
      <figcaption>
        <span class="media-name" title="<?= e($item['original_name']) ?>"><?= e($item['original_name']) ?></span>
        <span class="meta"><?= $item['width'] ? (int) $item['width'] . '×' . (int) $item['height'] . ' · ' : '' ?><?= human_filesize((int) $item['size']) ?></span>
        <form method="post" action="<?= e(url('/admin/media/' . $item['id'])) ?>" class="alt-form">
          <?= csrf_field() ?>
          <input type="text" name="alt" value="<?= e($item['alt'] ?? '') ?>" placeholder="Alt text" aria-label="Alt text">
          <button class="btn btn-sm" type="submit">Save</button>
        </form>
        <div class="media-item-actions">
          <button type="button" class="btn btn-sm" data-copy="<?= e(absolute_url($item['path'])) ?>"><?= icon('copy', 'icon icon-sm') ?>Copy URL</button>
          <form method="post" action="<?= e(url('/admin/media/' . $item['id'] . '/delete')) ?>" data-confirm="Delete this image? Pages that use it will show a broken image."><?= csrf_field() ?><button class="btn btn-sm btn-quiet" type="submit" aria-label="Delete"><?= icon('trash', 'icon icon-sm') ?></button></form>
        </div>
      </figcaption>
    </figure>
  <?php endforeach; ?>
</div>
<?php partial('admin/partials/pagination', ['pager' => $pager]); ?>
<?php else: ?>
<div class="card empty-state"><h2>No images yet</h2><p class="muted">Images you upload here, or from the editor, appear in this library.</p></div>
<?php endif; ?>
