<?php $status ??= 404; ?>
<section class="page-hero error-page">
  <div class="container narrow">
    <p class="error-code"><?= (int) $status ?></p>
    <h1 class="page-title"><?= $status === 404 ? 'This page took a different path' : 'Something went wrong' ?></h1>
    <p class="page-lead"><?= e($message ?: ($status === 404 ? "The page you're looking for doesn't exist or has moved." : 'Please try again in a moment.')) ?></p>
    <div class="cta-actions">
      <a class="btn btn-primary" href="<?= e(url('/')) ?>">Back to home</a>
      <a class="btn btn-ghost" href="<?= e(url('/?chat=1')) ?>"><?= icon('sparkles', 'icon icon-sm') ?>Ask BRIX AI</a>
    </div>
  </div>
</section>
