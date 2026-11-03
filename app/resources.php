<?php
declare(strict_types=1);

/**
 * Lists the admin edits with one generic screen: each entry names its table,
 * the columns shown in the list, and the form fields.
 *
 * Field types: text, textarea, lines (one item per line), number, decimal,
 * bool, select, url, image (media library path), slug (generated from another field).
 */
function resource_definitions(): array
{
    return [
        'products' => [
            'table'    => 'products',
            'title'    => 'Products',
            'singular' => 'product',
            'icon'     => 'package',
            'intro'    => 'The apps shown under the chat on the homepage. BRIX AI recommends one of these at the end of every answer, so keep the description and keywords accurate.',
            'columns'  => ['name' => 'Name', 'tagline' => 'Tagline', 'metric_value' => 'Metric'],
            'fields'   => [
                'name'           => ['label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 120],
                'slug'           => ['label' => 'Slug', 'type' => 'slug', 'from' => 'name', 'help' => 'Short ID the AI uses to recommend this product.'],
                'tagline'        => ['label' => 'Tagline', 'type' => 'text', 'max' => 200],
                'description'    => ['label' => 'What it does', 'type' => 'textarea', 'help' => 'BRIX AI reads this when deciding what to recommend.'],
                'features'       => ['label' => 'Key features', 'type' => 'lines', 'help' => 'One per line. The first three appear on the recommendation card.'],
                'icon'           => ['label' => 'Icon', 'type' => 'select', 'options' => icon_options()],
                'image'          => ['label' => 'Image (optional, replaces the icon)', 'type' => 'image'],
                'metric_value'   => ['label' => 'Headline metric', 'type' => 'text', 'max' => 40, 'placeholder' => '+41%'],
                'metric_label'   => ['label' => 'Metric label', 'type' => 'text', 'max' => 120, 'placeholder' => 'AOV for a skincare brand'],
                'install_url'    => ['label' => 'Install URL', 'type' => 'url', 'help' => 'Leave empty to use the install link from Site settings.'],
                'learn_more_url' => ['label' => 'Learn more link', 'type' => 'text', 'placeholder' => '/features/cart-upsell', 'help' => 'A page on this site or a full URL. Optional.'],
                'ai_keywords'    => ['label' => 'Matching keywords', 'type' => 'textarea', 'help' => 'Comma-separated words that should point to this product, e.g. "free shipping, threshold, gift".'],
                'is_active'      => ['label' => 'Show on the website', 'type' => 'bool', 'default' => 1],
                'sort_order'     => ['label' => 'Order', 'type' => 'number', 'default' => 0],
            ],
        ],
        'plans' => [
            'table'    => 'pricing_plans',
            'title'    => 'Pricing plans',
            'singular' => 'plan',
            'icon'     => 'dollar',
            'intro'    => 'Plans in the pricing section. Leave the yearly price empty to hide the yearly option for a plan.',
            'columns'  => ['name' => 'Plan', 'price_monthly' => 'Monthly', 'price_yearly' => 'Yearly', 'badge' => 'Badge'],
            'fields'   => [
                'name'           => ['label' => 'Plan name', 'type' => 'text', 'required' => true, 'max' => 80],
                'tagline'        => ['label' => 'Tagline', 'type' => 'text', 'max' => 160],
                'price_monthly'  => ['label' => 'Price per month', 'type' => 'decimal', 'default' => 0],
                'price_yearly'   => ['label' => 'Price per year', 'type' => 'decimal', 'nullable' => true],
                'features'       => ['label' => 'Features', 'type' => 'lines', 'help' => 'One per line.'],
                'cta_label'      => ['label' => 'Button label', 'type' => 'text', 'max' => 60, 'placeholder' => 'Start free trial'],
                'cta_url'        => ['label' => 'Button link', 'type' => 'url', 'help' => 'Leave empty to use the install link.'],
                'badge'          => ['label' => 'Badge', 'type' => 'text', 'max' => 40, 'placeholder' => 'Most popular'],
                'is_highlighted' => ['label' => 'Highlight this plan', 'type' => 'bool', 'default' => 0],
                'is_active'      => ['label' => 'Show on the website', 'type' => 'bool', 'default' => 1],
                'sort_order'     => ['label' => 'Order', 'type' => 'number', 'default' => 0],
            ],
        ],
        'testimonials' => [
            'table'    => 'testimonials',
            'title'    => 'Testimonials',
            'singular' => 'testimonial',
            'icon'     => 'quote',
            'intro'    => 'Merchant quotes for the homepage.',
            'columns'  => ['name' => 'Name', 'metric_value' => 'Metric', 'quote' => 'Quote'],
            'fields'   => [
                'name'         => ['label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 120],
                'role'         => ['label' => 'Role and store', 'type' => 'text', 'max' => 160, 'placeholder' => 'Founder, Glow Skincare'],
                'quote'        => ['label' => 'Quote', 'type' => 'textarea', 'required' => true],
                'avatar'       => ['label' => 'Photo', 'type' => 'image'],
                'metric_value' => ['label' => 'Result', 'type' => 'text', 'max' => 40, 'placeholder' => '+41%'],
                'metric_label' => ['label' => 'Result label', 'type' => 'text', 'max' => 80, 'placeholder' => 'AOV'],
                'rating'       => ['label' => 'Stars (1-5)', 'type' => 'number', 'default' => 5, 'min' => 1, 'max_value' => 5],
                'is_active'    => ['label' => 'Show on the website', 'type' => 'bool', 'default' => 1],
                'sort_order'   => ['label' => 'Order', 'type' => 'number', 'default' => 0],
            ],
        ],
        'faqs' => [
            'table'    => 'faqs',
            'title'    => 'FAQs',
            'singular' => 'question',
            'icon'     => 'help',
            'intro'    => 'Questions in the homepage FAQ. For answers BRIX AI should know, add a document to the knowledge base as well.',
            'columns'  => ['question' => 'Question'],
            'fields'   => [
                'question'   => ['label' => 'Question', 'type' => 'text', 'required' => true, 'max' => 255],
                'answer'     => ['label' => 'Answer', 'type' => 'textarea', 'required' => true],
                'is_active'  => ['label' => 'Show on the website', 'type' => 'bool', 'default' => 1],
                'sort_order' => ['label' => 'Order', 'type' => 'number', 'default' => 0],
            ],
        ],
        'suggestions' => [
            'table'    => 'suggestions',
            'title'    => 'Question tags',
            'singular' => 'question tag',
            'icon'     => 'message',
            'intro'    => 'The clickable questions under the chat box. Clicking one opens the chat and asks it.',
            'columns'  => ['label' => 'Tag', 'prompt' => 'Question asked', 'placement' => 'Shown'],
            'fields'   => [
                'label'      => ['label' => 'Tag text', 'type' => 'text', 'required' => true, 'max' => 120],
                'prompt'     => ['label' => 'Full question', 'type' => 'text', 'max' => 500, 'help' => 'What gets asked. Leave empty to ask the tag text.'],
                'placement'  => ['label' => 'Show under', 'type' => 'select', 'options' => ['both' => 'Hero and empty chat', 'hero' => 'Hero only', 'chat' => 'Empty chat only']],
                'is_active'  => ['label' => 'Show on the website', 'type' => 'bool', 'default' => 1],
                'sort_order' => ['label' => 'Order', 'type' => 'number', 'default' => 0],
            ],
        ],
    ];
}

function resource_def(string $name): array
{
    $defs = resource_definitions();
    if (!isset($defs[$name])) {
        abort(404);
    }
    return $defs[$name] + ['name' => $name];
}

/**
 * Read a resource form from $_POST.
 *
 * @return array{0: array, 1: array<string,string>} values and errors
 */
function resource_values(array $def, ?array $existing): array
{
    $values = [];
    $errors = [];

    foreach ($def['fields'] as $key => $field) {
        $raw = $_POST[$key] ?? null;
        $label = $field['label'];

        switch ($field['type']) {
            case 'bool':
                $values[$key] = empty($raw) ? 0 : 1;
                break;
            case 'number':
                $value = is_numeric($raw) ? (int) $raw : (int) ($field['default'] ?? 0);
                if (isset($field['min'])) {
                    $value = max($field['min'], $value);
                }
                if (isset($field['max_value'])) {
                    $value = min($field['max_value'], $value);
                }
                $values[$key] = $value;
                break;
            case 'decimal':
                $raw = trim((string) $raw);
                if ($raw === '' && !empty($field['nullable'])) {
                    $values[$key] = null;
                } elseif ($raw !== '' && !is_numeric($raw)) {
                    $errors[$key] = "$label must be a number.";
                } else {
                    $values[$key] = round((float) $raw, 2);
                }
                break;
            case 'select':
                $raw = (string) $raw;
                $values[$key] = array_key_exists($raw, $field['options']) ? $raw : array_key_first($field['options']);
                break;
            case 'url':
                $raw = trim((string) $raw);
                if ($raw !== '' && !preg_match('#^https?://#i', $raw)) {
                    $errors[$key] = "$label must start with http:// or https://";
                }
                $values[$key] = $raw === '' ? null : $raw;
                break;
            case 'lines':
                $values[$key] = implode("\n", lines((string) $raw));
                break;
            case 'slug':
                $slug = slugify((string) $raw);
                if ($slug === '') {
                    $slug = slugify((string) ($_POST[$field['from']] ?? ''));
                }
                $values[$key] = unique_slug($def['table'], $slug, $existing['id'] ?? null);
                break;
            default:
                $raw = trim((string) $raw);
                if (isset($field['max']) && mb_strlen($raw) > $field['max']) {
                    $errors[$key] = "$label can be at most {$field['max']} characters.";
                }
                $values[$key] = $raw === '' ? null : $raw;
        }

        if (!empty($field['required']) && ($values[$key] === null || $values[$key] === '')) {
            $errors[$key] = "$label is required.";
        }
    }

    return [$values, $errors];
}
