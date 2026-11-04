<?php
declare(strict_types=1);

// ---- Generic lists (products, plans, testimonials, FAQs, question tags) -----

function admin_resource_index(string $resource): void
{
    require_admin();
    $def = resource_def($resource);
    $rows = db_all("SELECT * FROM `{$def['table']}` ORDER BY sort_order, id");
    render('admin/resource/index', ['title' => $def['title'], 'def' => $def, 'rows' => $rows], 'admin/layout');
}

function admin_resource_form(string $resource, ?string $id = null): void
{
    require_admin();
    $def = resource_def($resource);
    $row = $id ? (db_one("SELECT * FROM `{$def['table']}` WHERE id = ?", [(int) $id]) ?? abort(404)) : null;
    $errors = [];

    if (is_post()) {
        verify_csrf();
        [$values, $errors] = resource_values($def, $row);
        if (!$errors) {
            if ($row) {
                db_update($def['table'], $values + ['updated_at' => now()], 'id = ?', [$row['id']]);
            } else {
                db_insert($def['table'], $values + ['created_at' => now()]);
            }
            forget_input();
            flash('success', ucfirst($def['singular']) . ' saved.');
            redirect('/admin/r/' . $resource);
        }
        remember_input();
    }

    render('admin/resource/form', [
        'title'  => ($row ? 'Edit ' : 'New ') . $def['singular'],
        'def'    => $def,
        'row'    => $row,
        'errors' => $errors,
    ], 'admin/layout');
    forget_input();
}

function admin_resource_toggle(string $resource, string $id): void
{
    require_admin();
    verify_csrf();
    $def = resource_def($resource);
    db_exec("UPDATE `{$def['table']}` SET is_active = 1 - is_active WHERE id = ?", [(int) $id]);
    back('/admin/r/' . $resource);
}

function admin_resource_delete(string $resource, string $id): void
{
    require_admin();
    verify_csrf();
    $def = resource_def($resource);
    db_exec("DELETE FROM `{$def['table']}` WHERE id = ?", [(int) $id]);
    flash('success', ucfirst($def['singular']) . ' deleted.');
    redirect('/admin/r/' . $resource);
}

// ---- Homepage sections -------------------------------------------------------

/** The homepage editor, one group per section. Repeater rows are stored as JSON. */
function homepage_sections(): array
{
    $text = static fn (string $label, string $type = 'text') => ['label' => $label, 'type' => $type];
    return [
        'hero' => ['title' => 'Hero and chat', 'fields' => [
            'hero_eyebrow'     => $text('Small label above the heading'),
            'hero_title'       => $text('Heading'),
            'hero_subtitle'    => $text('Subheading', 'textarea'),
            'chat_placeholder' => $text('Chat box placeholder'),
            'chat_hint'        => $text('Hint under the chat box'),
            'products_title'   => $text('Heading above the product cards'),
        ]],
        'brands' => ['title' => 'Brand logos', 'toggle' => 'home_brands_enabled', 'note' => 'The scrolling logo strip under the hero. Upload a logo in the Media library and paste its path. Use logos with a transparent background; they show in their original colours.', 'fields' => [
            'home_brands' => ['label' => 'Brands', 'type' => 'repeater', 'schema' => [
                ['key' => 'name', 'label' => 'Brand name', 'type' => 'text'],
                ['key' => 'logo', 'label' => 'Logo path', 'type' => 'text'],
            ]],
        ]],
        'stats' => ['title' => 'Stats strip', 'toggle' => 'home_stats_enabled', 'fields' => [
            'home_stats' => ['label' => 'Stats', 'type' => 'repeater', 'schema' => [
                ['key' => 'value', 'label' => 'Number', 'type' => 'text'],
                ['key' => 'label', 'label' => 'Label', 'type' => 'text'],
            ]],
        ]],
        'apps' => ['title' => 'BRIX apps', 'toggle' => 'home_apps_enabled', 'note' => 'One row per app, in the order a shopper meets them. Each row shows as a step in the journey strip and as its own showcase further down. Install links, names and logos come from Website → Products.', 'fields' => [
            'home_apps_eyebrow'  => $text('Label'),
            'home_apps_title'    => $text('Heading'),
            'home_apps_subtitle' => $text('Subheading', 'textarea'),
            'home_apps'          => ['label' => 'Apps', 'type' => 'repeater', 'schema' => [
                ['key' => 'product', 'label' => 'Product', 'type' => 'select', 'options' => product_options()],
                ['key' => 'stage', 'label' => 'Journey step (e.g. "Before they buy")', 'type' => 'text'],
                ['key' => 'problem', 'label' => 'Problem it fixes (journey strip)', 'type' => 'textarea'],
                ['key' => 'title', 'label' => 'Showcase heading', 'type' => 'text'],
                ['key' => 'text', 'label' => 'Showcase text', 'type' => 'textarea'],
                ['key' => 'points', 'label' => 'Key features, one per line', 'type' => 'textarea'],
                ['key' => 'visual', 'label' => 'Illustration', 'type' => 'select', 'options' => ['tryon' => 'Virtual try-on', 'convert' => 'Interactive cart', 'connect' => 'Push notifications', 'none' => 'None (product logo)']],
            ]],
        ]],
        'how' => ['title' => 'How BRIX AI works', 'toggle' => 'home_how_enabled', 'fields' => [
            'home_how_eyebrow'  => $text('Label'),
            'home_how_title'    => $text('Heading'),
            'home_how_subtitle' => $text('Subheading', 'textarea'),
            'home_how_steps'    => ['label' => 'Steps', 'type' => 'repeater', 'schema' => [
                ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['key' => 'text', 'label' => 'Text', 'type' => 'textarea'],
            ]],
        ]],
        'results' => ['title' => 'Case studies', 'toggle' => 'home_results_enabled', 'note' => 'Cards come from knowledge-base case studies marked "Feature on homepage".', 'fields' => [
            'home_results_eyebrow'  => $text('Label'),
            'home_results_title'    => $text('Heading'),
            'home_results_subtitle' => $text('Subheading', 'textarea'),
        ]],
        'testimonials' => ['title' => 'Testimonials', 'toggle' => 'home_testimonials_enabled', 'note' => 'Quotes are managed under Website → Testimonials.', 'fields' => [
            'home_testimonials_eyebrow' => $text('Label'),
            'home_testimonials_title'   => $text('Heading'),
        ]],
        'why' => ['title' => 'Why BRIX', 'toggle' => 'home_why_enabled', 'fields' => [
            'home_why_eyebrow'  => $text('Label'),
            'home_why_title'    => $text('Heading'),
            'home_why_subtitle' => $text('Subheading', 'textarea'),
            'home_why'          => ['label' => 'Reasons', 'type' => 'repeater', 'schema' => [
                ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['key' => 'text', 'label' => 'Text', 'type' => 'textarea'],
            ]],
        ]],
        'blog' => ['title' => 'Latest posts', 'toggle' => 'home_blog_enabled', 'fields' => [
            'home_blog_eyebrow' => $text('Label'),
            'home_blog_title'   => $text('Heading'),
        ]],
        'pricing' => ['title' => 'Pricing', 'toggle' => 'home_pricing_enabled', 'note' => 'Plans are managed under Website → Pricing plans.', 'fields' => [
            'home_pricing_eyebrow'  => $text('Label'),
            'home_pricing_title'    => $text('Heading'),
            'home_pricing_subtitle' => $text('Subheading', 'textarea'),
        ]],
        'faq' => ['title' => 'FAQ', 'toggle' => 'home_faq_enabled', 'note' => 'Questions are managed under Website → FAQs.', 'fields' => [
            'home_faq_eyebrow' => $text('Label'),
            'home_faq_title'   => $text('Heading'),
        ]],
        'cta' => ['title' => 'Closing call to action', 'toggle' => 'home_cta_enabled', 'fields' => [
            'home_cta_title'  => $text('Heading'),
            'home_cta_text'   => $text('Text', 'textarea'),
        ]],
    ];
}

function admin_homepage(): void
{
    require_admin();
    $sections = homepage_sections();

    if (is_post()) {
        verify_csrf();
        $values = [];
        foreach ($sections as $section) {
            if (!empty($section['toggle'])) {
                $values[$section['toggle']] = input_bool($section['toggle']) ? '1' : '0';
            }
            foreach ($section['fields'] as $key => $field) {
                $raw = (string) ($_POST[$key] ?? '');
                if ($field['type'] === 'repeater') {
                    $rows = json_decode($raw, true);
                    $clean = [];
                    foreach (is_array($rows) ? $rows : [] as $row) {
                        $item = [];
                        foreach ($field['schema'] as $col) {
                            $item[$col['key']] = trim((string) ($row[$col['key']] ?? ''));
                        }
                        if (implode('', $item) !== '') {
                            $clean[] = $item;
                        }
                    }
                    $values[$key] = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    $values[$key] = trim($raw);
                }
            }
        }
        settings_save($values);
        flash('success', 'Homepage saved.');
        redirect('/admin/homepage' . (input('section') ? '#' . preg_replace('/[^a-z]/', '', (string) input('section')) : ''));
    }

    render('admin/homepage', ['title' => 'Homepage', 'sections' => $sections], 'admin/layout');
}

// ---- Site settings -----------------------------------------------------------

const SITE_SETTING_KEYS = [
    'site_name', 'site_tagline', 'site_logo', 'site_mark', 'site_favicon', 'contact_email', 'install_url', 'demo_url',
    'social_x', 'social_linkedin', 'social_instagram', 'social_youtube', 'footer_text',
    'seo_title', 'seo_description', 'og_image', 'currency_symbol', 'head_code', 'body_code',
];

function admin_settings(): void
{
    require_admin('admin');
    $errors = [];

    if (is_post()) {
        verify_csrf();
        $values = [];
        foreach (SITE_SETTING_KEYS as $key) {
            $values[$key] = trim((string) ($_POST[$key] ?? ''));
        }
        foreach (['install_url', 'demo_url', 'social_x', 'social_linkedin', 'social_instagram', 'social_youtube'] as $key) {
            if ($values[$key] !== '' && !preg_match('#^https?://#i', $values[$key])) {
                $errors[$key] = 'Use a full link starting with https://';
            }
        }
        if ($values['contact_email'] !== '' && !filter_var($values['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'That email address is not valid.';
        }
        if ($values['site_name'] === '') {
            $errors['site_name'] = 'The site needs a name.';
        }
        if (!$errors) {
            settings_save($values);
            flash('success', 'Settings saved.');
            redirect('/admin/settings');
        }
        remember_input();
    }

    render('admin/settings', ['title' => 'Site settings', 'errors' => $errors], 'admin/layout');
    forget_input();
}

// ---- Users -------------------------------------------------------------------

function admin_users(): void
{
    require_admin('admin');
    render('admin/users/index', ['title' => 'Users', 'users' => db_all('SELECT id, name, email, role, last_login_at, created_at FROM users ORDER BY name')], 'admin/layout');
}

function admin_user_form(?string $id = null): void
{
    $me = require_admin('admin');
    $user = $id ? (db_one('SELECT id, name, email, role FROM users WHERE id = ?', [(int) $id]) ?? abort(404)) : null;
    $errors = [];

    if (is_post()) {
        verify_csrf();
        $name = mb_substr((string) input('name', ''), 0, 120);
        $email = mb_strtolower((string) input('email', ''));
        $role = input('role') === 'editor' ? 'editor' : 'admin';
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '') {
            $errors['name'] = 'Enter a name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (db_val('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $user['id'] ?? 0])) {
            $errors['email'] = 'Another user already has that email.';
        }
        if (!$user || $password !== '') {
            if ($problem = password_problem($password)) {
                $errors['password'] = $problem;
            }
        }
        if ($user && (int) $user['id'] === (int) $me['id'] && $role !== 'admin') {
            $errors['role'] = 'You cannot remove your own administrator role.';
        }

        if (!$errors) {
            $data = ['name' => $name, 'email' => $email, 'role' => $role, 'updated_at' => now()];
            if ($password !== '') {
                $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            if ($user) {
                db_update('users', $data, 'id = ?', [$user['id']]);
            } else {
                db_insert('users', $data + ['created_at' => now()]);
            }
            forget_input();
            flash('success', 'User saved.');
            redirect('/admin/users');
        }
        remember_input();
    }

    render('admin/users/form', ['title' => $user ? 'Edit user' : 'New user', 'user' => $user, 'errors' => $errors], 'admin/layout');
    forget_input();
}

function admin_user_delete(string $id): void
{
    $me = require_admin('admin');
    verify_csrf();
    if ((int) $id === (int) $me['id']) {
        flash('error', 'You cannot delete your own account.');
    } else {
        db_exec('DELETE FROM users WHERE id = ?', [(int) $id]);
        flash('success', 'User deleted.');
    }
    redirect('/admin/users');
}

function admin_account(): void
{
    $me = require_admin();
    $errors = [];
    if (is_post()) {
        verify_csrf();
        $row = db_one('SELECT password_hash FROM users WHERE id = ?', [$me['id']]);
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['password'] ?? '');
        $name = mb_substr((string) input('name', ''), 0, 120);

        if ($name === '') {
            $errors['name'] = 'Enter your name.';
        }
        if ($new !== '') {
            if (!password_verify($current, (string) $row['password_hash'])) {
                $errors['current_password'] = 'Your current password is not correct.';
            } elseif ($problem = password_problem($new)) {
                $errors['password'] = $problem;
            } elseif ($new !== (string) ($_POST['password_confirm'] ?? '')) {
                $errors['password_confirm'] = 'The two new passwords do not match.';
            }
        }
        if (!$errors) {
            $data = ['name' => $name, 'updated_at' => now()];
            if ($new !== '') {
                $data['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
            }
            db_update('users', $data, 'id = ?', [$me['id']]);
            flash('success', $new !== '' ? 'Profile and password updated.' : 'Profile updated.');
            redirect('/admin/account');
        }
    }
    render('admin/account', ['title' => 'My account', 'me' => $me, 'errors' => $errors], 'admin/layout');
}
