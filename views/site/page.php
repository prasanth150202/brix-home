<?php
/** @var array $page */
$css = trim((string) $page['custom_css']);
$width = $page['template'] === 'wide' ? '' : 'narrow';
?>
<?php if ($css !== ''): ?><style><?= str_ireplace('</style', '', $css) ?></style><?php endif; ?>
<?php if ($page['status'] !== 'published'): ?>
  <div class="preview-bar">Draft preview. Only signed-in admins can see this page. <a href="<?= e(url('/admin/pages/' . $page['id'])) ?>">Edit page</a></div>
<?php endif; ?>

<?php if ($page['template'] === 'blank'): ?>
  <div class="page-blank"><?= $page['content'] ?></div>
<?php else: ?>
  <section class="page-hero">
    <div class="container <?= $width ?>">
      <h1 class="page-title" data-split><?= e($page['title']) ?></h1>
      <?php if ($page['meta_description']): ?><p class="page-lead"><?= e($page['meta_description']) ?></p><?php endif; ?>
    </div>
  </section>
  <div class="slab theme-light">
    <section class="page-body">
      <div class="container <?= $width ?>">
        <div class="prose"><?= $page['content'] ?></div>
      </div>
    </section>
  </div>
<?php endif; ?>
