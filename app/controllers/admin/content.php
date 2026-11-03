<?php
declare(strict_types=1);

// ---- Pages -----------------------------------------------------------------

const PAGE_TEMPLATES = [
    'default' => 'Standard: title banner and readable column',
    'wide'    => 'Wide: title banner and full-width content',
    'blank'   => 'Blank: only your content between header and footer (landing pages)',
];

function admin_pages(): void
{
    require_admin();
    $pages = db_all('SELECT id, title, slug, status, template, show_in_header, show_in_footer, updated_at, created_at FROM pages ORDER BY menu_order, title');
    render('admin/pages/index', ['title' => 'Pages', 'pages' => $pages], 'admin/layout');
}

function admin_page_form(?string $id = null): void
{
    $user = require_admin();
    $page = $id ? (db_one('SELECT * FROM pages WHERE id = ?', [(int) $id]) ?? abort(404)) : null;
    $errors = [];

    if (is_post()) {
        verify_csrf();
        $title = mb_substr((string) input('title', ''), 0, 255);
        $slug = slugify_path((string) input('slug', '')) ?: slugify($title);
        $template = (string) input('template', 'default');
        $status = input('status') === 'published' ? 'published' : 'draft';

        if ($title === '') {
            $errors['title'] = 'Give the page a title.';
        }
        if ($slug === '') {
            $errors['slug'] = 'Enter the URL for this page.';
        } elseif (in_array(explode('/', $slug)[0], RESERVED_SLUGS, true)) {
            $errors['slug'] = '/' . explode('/', $slug)[0] . ' is used by the site itself. Choose another URL.';
        } elseif (db_val('SELECT id FROM pages WHERE slug = ? AND id <> ?', [$slug, $page['id'] ?? 0])) {
            $errors['slug'] = "Another page already uses /$slug.";
        }

        $data = [
            'title'            => $title,
            'slug'             => $slug,
            'content'          => (string) ($_POST['content'] ?? ''),
            'template'         => array_key_exists($template, PAGE_TEMPLATES) ? $template : 'default',
            'custom_css'       => trim((string) ($_POST['custom_css'] ?? '')) ?: null,
            'meta_title'       => mb_substr((string) input('meta_title', ''), 0, 255) ?: null,
            'meta_description' => mb_substr((string) input('meta_description', ''), 0, 500) ?: null,
            'og_image'         => (string) input('og_image', '') ?: null,
            'noindex'          => input_bool('noindex'),
            'status'           => $status,
            'show_in_header'   => input_bool('show_in_header'),
            'show_in_footer'   => input_bool('show_in_footer'),
            'menu_order'       => input_int('menu_order'),
            'updated_at'       => now(),
        ];

        if (!$errors) {
            if ($status === 'published' && empty($page['published_at'])) {
                $data['published_at'] = now();
            }
            if ($page) {
                db_update('pages', $data, 'id = ?', [$page['id']]);
                $pageId = (int) $page['id'];
            } else {
                $pageId = db_insert('pages', $data + ['author_id' => $user['id'], 'created_at' => now()]);
            }
            forget_input();
            flash('success', $status === 'published' ? "Page published at /$slug." : 'Draft saved.');
            redirect('/admin/pages/' . $pageId);
        }
        remember_input();
    }

    render('admin/pages/form', ['title' => $page ? 'Edit page' : 'New page', 'page' => $page, 'errors' => $errors], 'admin/layout');
    forget_input();
}

function admin_page_delete(string $id): void
{
    require_admin();
    verify_csrf();
    db_exec('DELETE FROM pages WHERE id = ?', [(int) $id]);
    flash('success', 'Page deleted.');
    redirect('/admin/pages');
}

// ---- Blog posts --------------------------------------------------------------

function admin_posts(): void
{
    require_admin();
    $status = (string) ($_GET['status'] ?? '');
    $categoryId = (int) ($_GET['category'] ?? 0);
    $search = trim((string) ($_GET['q'] ?? ''));

    $where = ['1 = 1'];
    $params = [];
    if ($status === 'draft') {
        $where[] = "p.status = 'draft'";
    } elseif ($status === 'published') {
        $where[] = "p.status = 'published' AND p.published_at <= ?";
        $params[] = now();
    } elseif ($status === 'scheduled') {
        $where[] = "p.status = 'published' AND p.published_at > ?";
        $params[] = now();
    }
    if ($categoryId) {
        $where[] = 'p.category_id = ?';
        $params[] = $categoryId;
    }
    if ($search !== '') {
        $where[] = 'p.title LIKE ?';
        $params[] = "%$search%";
    }
    $sqlWhere = implode(' AND ', $where);
    $pager = paginate((int) db_val("SELECT COUNT(*) FROM posts p WHERE $sqlWhere", $params), 20, max(1, (int) ($_GET['page'] ?? 1)));
    $posts = db_all(
        "SELECT p.id, p.title, p.slug, p.status, p.published_at, p.updated_at, p.views, p.is_featured, p.featured_image, c.name AS category_name
           FROM posts p LEFT JOIN categories c ON c.id = p.category_id
          WHERE $sqlWhere
          ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
          LIMIT {$pager['per_page']} OFFSET {$pager['offset']}",
        $params
    );

    render('admin/posts/index', [
        'title'      => 'Blog posts',
        'posts'      => $posts,
        'pager'      => $pager,
        'status'     => $status,
        'categoryId' => $categoryId,
        'search'     => $search,
        'categories' => db_all('SELECT id, name FROM categories ORDER BY sort_order, name'),
    ], 'admin/layout');
}

function admin_post_form(?string $id = null): void
{
    $user = require_admin();
    $post = $id ? (db_one('SELECT * FROM posts WHERE id = ?', [(int) $id]) ?? abort(404)) : null;
    $errors = [];

    if (is_post()) {
        verify_csrf();
        $title = mb_substr((string) input('title', ''), 0, 255);
        $slug = slugify((string) input('slug', '')) ?: slugify($title);
        $status = input('status') === 'published' ? 'published' : 'draft';
        $content = (string) ($_POST['content'] ?? '');
        $publishedAt = trim((string) input('published_at', ''));
        $categoryId = input_int('category_id');

        if ($title === '') {
            $errors['title'] = 'Give the post a title.';
        }
        if ($slug === '') {
            $errors['slug'] = 'Enter a URL slug.';
        } elseif (db_val('SELECT id FROM posts WHERE slug = ? AND id <> ?', [$slug, $post['id'] ?? 0])) {
            $errors['slug'] = "Another post already uses /blog/$slug.";
        }
        if ($publishedAt !== '' && strtotime($publishedAt) === false) {
            $errors['published_at'] = 'That date is not valid.';
        }

        $data = [
            'title'              => $title,
            'slug'               => $slug,
            'excerpt'            => trim((string) input('excerpt', '')) ?: null,
            'content'            => $content,
            'featured_image'     => (string) input('featured_image', '') ?: null,
            'featured_image_alt' => mb_substr((string) input('featured_image_alt', ''), 0, 255) ?: null,
            'category_id'        => $categoryId && db_val('SELECT id FROM categories WHERE id = ?', [$categoryId]) ? $categoryId : null,
            'author_name'        => mb_substr((string) input('author_name', ''), 0, 120) ?: null,
            'status'             => $status,
            'is_featured'        => input_bool('is_featured'),
            'meta_title'         => mb_substr((string) input('meta_title', ''), 0, 255) ?: null,
            'meta_description'   => mb_substr((string) input('meta_description', ''), 0, 500) ?: null,
            'og_image'           => (string) input('og_image', '') ?: null,
            'canonical_url'      => mb_substr((string) input('canonical_url', ''), 0, 255) ?: null,
            'noindex'            => input_bool('noindex'),
            'reading_minutes'    => reading_minutes($content),
            'updated_at'         => now(),
        ];
        if ($publishedAt !== '') {
            $data['published_at'] = date('Y-m-d H:i:s', (int) strtotime($publishedAt));
        } elseif ($status === 'published' && empty($post['published_at'])) {
            $data['published_at'] = now();
        }

        if (!$errors) {
            if ($post) {
                db_update('posts', $data, 'id = ?', [$post['id']]);
                $postId = (int) $post['id'];
            } else {
                $postId = db_insert('posts', $data + ['author_id' => $user['id'], 'created_at' => now()]);
            }
            sync_post_tags($postId, (string) input('tags', ''));
            forget_input();
            $when = $data['published_at'] ?? $post['published_at'] ?? null;
            $message = match (true) {
                $status === 'draft' => 'Draft saved.',
                $when !== null && strtotime($when) > time() => 'Post scheduled for ' . format_date($when, 'M j, Y g:i A') . '.',
                default => 'Post published.',
            };
            flash('success', $message);
            redirect('/admin/posts/' . $postId);
        }
        remember_input();
    }

    $tags = $post ? implode(', ', array_column(post_tags((int) $post['id']), 'name')) : '';
    render('admin/posts/form', [
        'title'      => $post ? 'Edit post' : 'New post',
        'post'       => $post,
        'tags'       => $tags,
        'errors'     => $errors,
        'categories' => db_all('SELECT id, name FROM categories ORDER BY sort_order, name'),
        'allTags'    => db_all('SELECT name FROM tags ORDER BY name'),
    ], 'admin/layout');
    forget_input();
}

function admin_post_delete(string $id): void
{
    require_admin();
    verify_csrf();
    db_exec('DELETE FROM posts WHERE id = ?', [(int) $id]);
    flash('success', 'Post deleted.');
    redirect('/admin/posts');
}

// ---- Categories and tags -----------------------------------------------------

function admin_categories(): void
{
    require_admin();
    $editing = isset($_GET['edit']) ? db_one('SELECT * FROM categories WHERE id = ?', [(int) $_GET['edit']]) : null;
    $errors = [];

    if (is_post()) {
        verify_csrf();
        $id = input_int('id');
        $name = mb_substr((string) input('name', ''), 0, 120);
        $slug = slugify((string) input('slug', '')) ?: slugify($name);
        if ($name === '') {
            $errors['name'] = 'Enter a name.';
        } elseif (db_val('SELECT id FROM categories WHERE slug = ? AND id <> ?', [$slug, $id])) {
            $errors['slug'] = 'Another category already uses that slug.';
        }
        if (!$errors) {
            $data = ['name' => $name, 'slug' => $slug, 'description' => trim((string) input('description', '')) ?: null, 'sort_order' => input_int('sort_order')];
            if ($id) {
                db_update('categories', $data, 'id = ?', [$id]);
            } else {
                db_insert('categories', $data + ['created_at' => now()]);
            }
            flash('success', 'Category saved.');
            redirect('/admin/categories');
        }
        $editing = ['id' => $id, 'name' => $name, 'slug' => $slug, 'description' => input('description'), 'sort_order' => input_int('sort_order')];
    }

    $categories = db_all('SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id = c.id) AS post_count FROM categories c ORDER BY c.sort_order, c.name');
    render('admin/taxonomy', [
        'title'      => 'Categories & tags',
        'categories' => $categories,
        'tags'       => db_all('SELECT t.*, (SELECT COUNT(*) FROM post_tags pt WHERE pt.tag_id = t.id) AS post_count FROM tags t ORDER BY t.name'),
        'editing'    => $editing,
        'errors'     => $errors,
    ], 'admin/layout');
}

function admin_category_delete(string $id): void
{
    require_admin();
    verify_csrf();
    db_exec('DELETE FROM categories WHERE id = ?', [(int) $id]);
    flash('success', 'Category deleted. Its posts are now uncategorised.');
    redirect('/admin/categories');
}

function admin_tag_delete(string $id): void
{
    require_admin();
    verify_csrf();
    db_exec('DELETE FROM tags WHERE id = ?', [(int) $id]);
    flash('success', 'Tag deleted.');
    redirect('/admin/categories');
}

// ---- Media library -----------------------------------------------------------

function admin_media(): void
{
    require_admin();
    if (is_post()) {
        verify_csrf();
        $saved = 0;
        $files = $_FILES['files'] ?? null;
        $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;
        for ($i = 0; $i < $count; $i++) {
            $file = ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
            try {
                upload_image($file);
                $saved++;
            } catch (Throwable $ex) {
                flash('error', $file['name'] . ': ' . $ex->getMessage());
            }
        }
        if ($saved) {
            flash('success', $saved === 1 ? 'Image uploaded.' : "$saved images uploaded.");
        }
        redirect('/admin/media');
    }

    $pager = paginate((int) db_val('SELECT COUNT(*) FROM media'), 48, max(1, (int) ($_GET['page'] ?? 1)));
    $items = db_all("SELECT * FROM media ORDER BY id DESC LIMIT {$pager['per_page']} OFFSET {$pager['offset']}");
    render('admin/media', ['title' => 'Media library', 'items' => $items, 'pager' => $pager], 'admin/layout');
}

/** JSON upload used by the editor and image fields. */
function admin_media_upload(): void
{
    require_admin();
    if (!csrf_valid()) {
        json_response(['error' => 'Session expired. Refresh the page.'], 419);
    }
    try {
        $media = upload_image($_FILES['file'] ?? [], (string) input('alt', ''));
    } catch (Throwable $ex) {
        json_response(['error' => $ex->getMessage()], 422);
    }
    json_response(['id' => $media['id'], 'path' => $media['path'], 'location' => media_url($media['path'])]);
}

function admin_media_list(): void
{
    require_admin();
    $items = db_all('SELECT id, path, original_name, width, height FROM media ORDER BY id DESC LIMIT 200');
    foreach ($items as &$item) {
        $item['url'] = media_url($item['path']);
    }
    json_response(['items' => $items]);
}

function admin_media_update(string $id): void
{
    require_admin();
    verify_csrf();
    db_update('media', ['alt' => mb_substr((string) input('alt', ''), 0, 255) ?: null], 'id = ?', [(int) $id]);
    flash('success', 'Alt text saved.');
    back('/admin/media');
}

function admin_media_delete(string $id): void
{
    require_admin();
    verify_csrf();
    delete_media((int) $id);
    flash('success', 'Image deleted.');
    back('/admin/media');
}
