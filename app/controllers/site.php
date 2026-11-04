<?php
declare(strict_types=1);

function site_home(): void
{
    $siteName = (string) setting('site_name');
    render('site/home', [
        'isHome'       => true,
        'meta'         => page_meta(['schema' => [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $siteName,
            'url'      => absolute_url('/'),
            'logo'     => absolute_url((string) setting('site_mark')),
        ]]),
        'products'     => active_products(),
        'apps'         => setting_on('home_apps_enabled') ? home_apps() : [],
        'heroTags'     => hero_suggestions('hero'),
        'chatTags'     => hero_suggestions('chat'),
        'caseStudies'  => setting_on('home_results_enabled') ? featured_case_studies(6) : [],
        'testimonials' => setting_on('home_testimonials_enabled') ? active_rows('testimonials') : [],
        'plans'        => setting_on('home_pricing_enabled') ? active_rows('pricing_plans') : [],
        'faqs'         => setting_on('home_faq_enabled') ? active_rows('faqs') : [],
        'posts'        => setting_on('home_blog_enabled') ? find_posts(['limit' => 3])['items'] : [],
    ]);
}

function site_page(string $path): void
{
    $slug = trim($path, '/');
    $page = find_page($slug);
    // Signed-in admins can preview drafts.
    if (!$page && current_admin()) {
        $page = db_one('SELECT * FROM pages WHERE slug = ?', [$slug]);
    }
    if (!$page) {
        abort(404);
    }

    render('site/page', [
        'page' => $page,
        'meta' => page_meta([
            'title'       => $page['meta_title'] ?: $page['title'],
            'description' => $page['meta_description'] ?: str_limit(plain_text($page['content']), 160),
            'image'       => $page['og_image'] ?: null,
            'noindex'     => (bool) $page['noindex'] || $page['status'] !== 'published',
        ]),
    ]);
}

function site_blog(): void
{
    blog_listing([], ['heading' => 'The BRIX blog', 'intro' => 'Practical guides and real case studies on growing a Shopify store.']);
}

function site_blog_category(string $slug): void
{
    $category = db_one('SELECT * FROM categories WHERE slug = ?', [$slug]) ?? abort(404);
    blog_listing(['category_id' => $category['id']], [
        'heading'  => $category['name'],
        'intro'    => $category['description'] ?: 'Articles filed under ' . $category['name'] . '.',
        'category' => $category,
    ]);
}

function site_blog_tag(string $slug): void
{
    $tag = db_one('SELECT * FROM tags WHERE slug = ?', [$slug]) ?? abort(404);
    blog_listing(['tag_id' => $tag['id']], ['heading' => '#' . $tag['name'], 'intro' => 'Articles tagged ' . $tag['name'] . '.', 'tag' => $tag]);
}

function blog_listing(array $filter, array $context): void
{
    $search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
    if ($search !== '') {
        $filter['search'] = $search;
    }
    $perPage = 9;
    $total = find_posts($filter + ['limit' => 1])['total'];
    $pager = paginate($total, $perPage, max(1, (int) ($_GET['page'] ?? 1)));
    $posts = find_posts($filter + ['limit' => $perPage, 'offset' => $pager['offset']])['items'];

    render('site/blog', $context + [
        'posts'      => $posts,
        'pager'      => $pager,
        'search'     => $search,
        'categories' => blog_categories(),
        'meta'       => page_meta([
            'title'       => $context['heading'] . ($pager['page'] > 1 ? ' (page ' . $pager['page'] . ')' : ''),
            'description' => $context['intro'],
            'noindex'     => $search !== '',
        ]),
    ]);
}

function site_post(string $slug): void
{
    $post = find_post($slug);
    if (!$post && current_admin()) {
        $post = db_one('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ?', [$slug]);
    }
    if (!$post) {
        abort(404);
    }
    if ($post['status'] === 'published') {
        db_exec('UPDATE posts SET views = views + 1 WHERE id = ?', [$post['id']]);
    }

    $image = $post['og_image'] ?: $post['featured_image'];
    render('site/post', [
        'post'    => $post,
        'tags'    => post_tags((int) $post['id']),
        'related' => related_posts($post, 3),
        'meta'    => page_meta([
            'title'       => $post['meta_title'] ?: $post['title'],
            'description' => $post['meta_description'] ?: ($post['excerpt'] ?: str_limit(plain_text($post['content']), 160)),
            'image'       => $image ?: null,
            'canonical'   => $post['canonical_url'] ?: null,
            'noindex'     => (bool) $post['noindex'] || $post['status'] !== 'published',
            'type'        => 'article',
            'schema'      => [
                '@context'      => 'https://schema.org',
                '@type'         => 'BlogPosting',
                'headline'      => $post['title'],
                'description'   => $post['excerpt'],
                'datePublished' => date('c', strtotime((string) $post['published_at'])),
                'dateModified'  => date('c', strtotime((string) ($post['updated_at'] ?: $post['published_at']))),
                'author'        => ['@type' => 'Person', 'name' => $post['author_name'] ?: setting('site_name')],
                'image'         => $image ? absolute_url($image) : null,
                'mainEntityOfPage' => absolute_url('/blog/' . $post['slug']),
            ],
        ]),
    ]);
}

function site_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');
    $urls = [['loc' => absolute_url('/'), 'lastmod' => null], ['loc' => absolute_url('/blog'), 'lastmod' => null]];
    foreach (db_all("SELECT slug, updated_at, published_at FROM pages WHERE status = 'published' AND noindex = 0") as $p) {
        $urls[] = ['loc' => absolute_url('/' . $p['slug']), 'lastmod' => $p['updated_at'] ?: $p['published_at']];
    }
    foreach (db_all("SELECT slug, updated_at, published_at FROM posts WHERE status = 'published' AND published_at <= ? AND noindex = 0", [now()]) as $p) {
        $urls[] = ['loc' => absolute_url('/blog/' . $p['slug']), 'lastmod' => $p['updated_at'] ?: $p['published_at']];
    }
    foreach (blog_categories() as $c) {
        $urls[] = ['loc' => absolute_url('/blog/category/' . $c['slug']), 'lastmod' => null];
    }

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        echo '  <url><loc>' . e($u['loc']) . '</loc>' . ($u['lastmod'] ? '<lastmod>' . date('Y-m-d', strtotime($u['lastmod'])) . '</lastmod>' : '') . "</url>\n";
    }
    echo '</urlset>';
}

function site_robots(): void
{
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nDisallow: /admin\nDisallow: /api/\n\nSitemap: " . absolute_url('/sitemap.xml') . "\n";
}
