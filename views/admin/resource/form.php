<?php
/**
 * @var array $def
 * @var ?array $row
 * @var array $errors
 */
$name = $def['name'];
$main = [];
$side = [];
foreach ($def['fields'] as $key => $field) {
    in_array($field['type'], ['bool', 'number'], true) || $key === 'icon' ? $side[$key] = $field : $main[$key] = $field;
}

$render = static function (string $key, array $field) use ($row, $errors, $def): string {
    $value = $row[$key] ?? ($field['default'] ?? '');
    $opts = [
        'error'       => $errors[$key] ?? null,
        'help'        => isset($field['help']) ? e($field['help']) : null,
        'placeholder' => $field['placeholder'] ?? null,
        'required'    => !empty($field['required']),
    ];
    return match ($field['type']) {
        'bool'     => form_field('checkbox', $key, $field['label'], $value, $opts),
        'textarea' => form_field('textarea', $key, $field['label'], $value, $opts + ['rows' => 4]),
        'lines'    => form_field('textarea', $key, $field['label'], $value, $opts + ['rows' => 5]),
        'select'   => form_field('select', $key, $field['label'], $value, $opts + ['options' => $field['options']]),
        'number'   => form_field('number', $key, $field['label'], $value, $opts),
        'decimal'  => form_field('number', $key, $field['label'], $value, $opts + ['attrs' => ['step' => '0.01', 'min' => '0']]),
        'url'      => form_field('url', $key, $field['label'], $value, $opts),
        'image'    => form_field('image', $key, $field['label'], $value, $opts),
        'slug'     => form_field('text', $key, $field['label'], $value, $opts + ['attrs' => ['data-slug-target' => true, 'data-slug-locked' => $row ? true : null]]),
        default    => form_field('text', $key, $field['label'], $value, $opts + ['attrs' => isset($def['fields']['slug']) && ($def['fields']['slug']['from'] ?? '') === $key ? ['data-slug-source' => 'slug'] : []]),
    };
};
?>
<div class="page-head">
  <a class="back-link" href="<?= e(url("/admin/r/$name")) ?>"><?= icon('arrow-left', 'icon icon-sm') ?><?= e($def['title']) ?></a>
</div>

<form method="post" class="form-layout">
  <?= csrf_field() ?>
  <div class="form-main">
    <section class="card">
      <?php foreach ($main as $key => $field): ?><?= $render($key, $field) ?><?php endforeach; ?>
    </section>
  </div>
  <aside class="form-side">
    <section class="card">
      <?php foreach ($side as $key => $field): ?><?= $render($key, $field) ?><?php endforeach; ?>
      <button class="btn btn-primary btn-block" type="submit">Save <?= e($def['singular']) ?></button>
    </section>
    <?php if ($name === 'products'): ?>
      <p class="muted small">Tip: BRIX AI reads the name, tagline, description, features and metric of every visible product, and picks one to recommend after each answer.</p>
    <?php endif; ?>
  </aside>
</form>
