<?php /** @var array $sections */ ?>
<div class="page-head">
  <p class="muted">Every heading and paragraph on the homepage. Products, plans, testimonials, FAQs and question tags have their own screens under Website.</p>
  <a class="btn" href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('eye', 'icon icon-sm') ?>View homepage</a>
</div>

<form method="post" class="homepage-form">
  <?= csrf_field() ?>
  <nav class="section-nav" aria-label="Sections">
    <?php foreach ($sections as $key => $section): ?><a href="#<?= e($key) ?>"><?= e($section['title']) ?></a><?php endforeach; ?>
  </nav>
  <div class="homepage-sections">
    <?php foreach ($sections as $key => $section): ?>
      <section class="card" id="<?= e($key) ?>">
        <div class="card-head">
          <h2><?= e($section['title']) ?></h2>
          <?php if (!empty($section['toggle'])): ?>
            <?= form_field('checkbox', $section['toggle'], 'Show section', setting_on($section['toggle'])) ?>
          <?php endif; ?>
        </div>
        <?php if (!empty($section['note'])): ?><p class="muted small"><?= e($section['note']) ?></p><?php endif; ?>
        <div class="field-grid">
          <?php foreach ($section['fields'] as $name => $field): ?>
            <?php if ($field['type'] === 'repeater'): ?>
              <?= repeater_field($name, $field['label'], $field['schema'], setting_rows($name)) ?>
            <?php else: ?>
              <?= form_field($field['type'], $name, $field['label'], setting($name), ['rows' => 2, 'wide' => $field['type'] === 'textarea']) ?>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
  <div class="sticky-save"><button class="btn btn-primary" type="submit">Save homepage</button></div>
</form>
