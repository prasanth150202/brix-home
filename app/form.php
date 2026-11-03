<?php
declare(strict_types=1);

/** The submitted value after a failed save, otherwise the stored one. */
function field_value(string $name, mixed $current = null): mixed
{
    if (is_post() && array_key_exists($name, $_POST)) {
        return $_POST[$name];
    }
    return $current;
}

function attrs(array $attrs): string
{
    $out = '';
    foreach ($attrs as $key => $value) {
        if ($value === null || $value === false) {
            continue;
        }
        $out .= $value === true ? ' ' . e($key) : ' ' . e($key) . '="' . e($value) . '"';
    }
    return $out;
}

/**
 * One labelled form field.
 *
 * Types: text, email, url, password, number, date, textarea, code, select, checkbox, image.
 * Options: help, error, placeholder, required, rows, options, attrs, wide, count, class.
 */
function form_field(string $type, string $name, string $label, mixed $value = null, array $opts = []): string
{
    $id = 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
    $error = $opts['error'] ?? null;
    $classes = trim('field ' . (!empty($opts['wide']) ? 'field-wide ' : '') . ($error ? 'has-error ' : '') . ($opts['class'] ?? ''));
    $extra = $opts['attrs'] ?? [];
    $help = !empty($opts['help']) ? '<p class="field-help">' . $opts['help'] . '</p>' : '';
    $errorHtml = $error ? '<p class="field-error">' . e($error) . '</p>' : '';

    if ($type === 'checkbox') {
        $checked = is_post() ? !empty($_POST[$name]) : (bool) $value;
        return '<div class="' . $classes . '"><label class="switch"><input type="checkbox" name="' . e($name) . '" value="1"' . ($checked ? ' checked' : '') . attrs($extra) . '><span class="switch-ui"></span><span class="switch-label">' . e($label) . '</span></label>' . $help . $errorHtml . '</div>';
    }

    $value = $type === 'password' ? '' : field_value($name, $value);
    $common = attrs([
        'id'          => $id,
        'name'        => $name,
        'placeholder' => $opts['placeholder'] ?? null,
        'required'    => !empty($opts['required']),
        'data-count'  => $opts['count'] ?? null,
    ] + $extra);

    $control = match ($type) {
        'textarea', 'code' => '<textarea' . $common . ' rows="' . (int) ($opts['rows'] ?? 4) . '"' . ($type === 'code' ? ' class="mono" spellcheck="false"' : '') . '>' . e($value) . '</textarea>',
        'select' => (function () use ($common, $opts, $value): string {
            $html = '<select' . $common . '>';
            foreach ($opts['options'] ?? [] as $optValue => $optLabel) {
                $html .= '<option value="' . e($optValue) . '"' . ((string) $optValue === (string) $value ? ' selected' : '') . '>' . e($optLabel) . '</option>';
            }
            return $html . '</select>';
        })(),
        'image' => media_picker($name, (string) $value, $id),
        default => '<input type="' . e($type) . '"' . $common . ' value="' . e($value) . '">',
    };

    $counter = !empty($opts['count']) ? '<span class="char-count" data-count-for="' . e($id) . '"></span>' : '';
    return '<div class="' . $classes . '"><label for="' . e($id) . '">' . e($label) . (!empty($opts['required']) ? ' <span class="req">*</span>' : '') . $counter . '</label>' . $control . $help . $errorHtml . '</div>';
}

/** An image path field with upload and media-library buttons. */
function media_picker(string $name, string $value, string $id): string
{
    $preview = $value !== '' ? '<img src="' . e(media_url($value)) . '" alt="">' : '<span>' . icon('image') . '</span>';
    return '<div class="media-picker" data-media-picker>'
        . '<div class="media-thumb">' . $preview . '</div>'
        . '<div class="media-body">'
        . '<input type="text" id="' . e($id) . '" name="' . e($name) . '" value="' . e($value) . '" placeholder="uploads/… or https://…">'
        . '<div class="media-buttons">'
        . '<button type="button" class="btn btn-sm" data-media-upload>' . icon('upload', 'icon icon-sm') . 'Upload</button>'
        . '<button type="button" class="btn btn-sm" data-media-library>' . icon('image', 'icon icon-sm') . 'Library</button>'
        . '<button type="button" class="btn btn-sm btn-quiet" data-media-clear>Remove</button>'
        . '<input type="file" accept="image/png,image/jpeg,image/gif,image/webp" hidden>'
        . '</div></div></div>';
}

/** A JSON list edited row by row in the browser (see admin.js). */
function repeater_field(string $name, string $label, array $schema, array $rows): string
{
    return '<div class="field field-wide"><label>' . e($label) . '</label>'
        . '<div class="repeater" data-repeater data-schema="' . e(json_encode($schema, JSON_UNESCAPED_UNICODE)) . '">'
        . '<input type="hidden" name="' . e($name) . '" value="' . e(json_encode($rows, JSON_UNESCAPED_UNICODE)) . '">'
        . '<div class="repeater-rows"></div>'
        . '<button type="button" class="btn btn-sm" data-repeater-add>' . icon('plus', 'icon icon-sm') . 'Add row</button>'
        . '</div></div>';
}

function status_badge(string $status, ?string $publishedAt = null): string
{
    if ($status === 'published' && $publishedAt && strtotime($publishedAt) > time()) {
        return '<span class="badge badge-amber">Scheduled</span>';
    }
    return match ($status) {
        'published', 'ready' => '<span class="badge badge-green">' . ($status === 'ready' ? 'Ready' : 'Published') . '</span>',
        'error' => '<span class="badge badge-red">Error</span>',
        'processing' => '<span class="badge badge-amber">Processing</span>',
        default => '<span class="badge">Draft</span>',
    };
}
