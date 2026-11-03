<?php
declare(strict_types=1);

/** Slugs a custom page may not take, because the site already uses them. */
const RESERVED_SLUGS = ['admin', 'api', 'blog', 'assets', 'uploads', 'storage', 'sitemap.xml', 'robots.txt', 'index.php'];

function page_url(array $page): string
{
    return url('/' . $page['slug']);
}

function post_url(array $post): string
{
    return url('/blog/' . $post['slug']);
}

function category_url(array $category): string
{
    return url('/blog/category/' . $category['slug']);
}

function tag_url(array $tag): string
{
    return url('/blog/tag/' . $tag['slug']);
}

function nav_pages(string $where): array
{
    static $cache = [];
    if (!isset($cache[$where])) {
        $column = $where === 'header' ? 'show_in_header' : 'show_in_footer';
        try {
            $cache[$where] = db_all(
                "SELECT title, slug FROM pages WHERE status = 'published' AND $column = 1 ORDER BY menu_order, title"
            );
        } catch (PDOException) {
            $cache[$where] = [];
        }
    }
    return $cache[$where];
}

function find_page(string $slug): ?array
{
    return db_one("SELECT * FROM pages WHERE slug = ? AND status = 'published' AND (published_at IS NULL OR published_at <= ?)", [$slug, now()]);
}

/**
 * Published posts with optional filters.
 *
 * @param array{category_id?:int, tag_id?:int, search?:string, exclude_id?:int, featured?:bool, limit?:int, offset?:int} $opts
 * @return array{items: list<array>, total: int}
 */
function find_posts(array $opts = []): array
{
    $where = ["p.status = 'published'", 'p.published_at <= ?'];
    $params = [now()];

    if (!empty($opts['category_id'])) {
        $where[] = 'p.category_id = ?';
        $params[] = (int) $opts['category_id'];
    }
    if (!empty($opts['tag_id'])) {
        $where[] = 'p.id IN (SELECT post_id FROM post_tags WHERE tag_id = ?)';
        $params[] = (int) $opts['tag_id'];
    }
    if (!empty($opts['exclude_id'])) {
        $where[] = 'p.id <> ?';
        $params[] = (int) $opts['exclude_id'];
    }
    if (!empty($opts['featured'])) {
        $where[] = 'p.is_featured = 1';
    }
    if (!empty($opts['search'])) {
        $where[] = '(MATCH(p.title, p.excerpt, p.content) AGAINST (? IN NATURAL LANGUAGE MODE) OR p.title LIKE ?)';
        $params[] = $opts['search'];
        $params[] = '%' . $opts['search'] . '%';
    }

    $sqlWhere = implode(' AND ', $where);
    $total = (int) db_val("SELECT COUNT(*) FROM posts p WHERE $sqlWhere", $params);
    $limit = max(1, (int) ($opts['limit'] ?? 9));
    $offset = max(0, (int) ($opts['offset'] ?? 0));

    $items = db_all(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug
           FROM posts p LEFT JOIN categories c ON c.id = p.category_id
          WHERE $sqlWhere
          ORDER BY p.published_at DESC, p.id DESC
          LIMIT $limit OFFSET $offset",
        $params
    );

    return ['items' => $items, 'total' => $total];
}

function find_post(string $slug): ?array
{
    return db_one(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug
           FROM posts p LEFT JOIN categories c ON c.id = p.category_id
          WHERE p.slug = ? AND p.status = 'published' AND p.published_at <= ?",
        [$slug, now()]
    );
}

function post_tags(int $postId): array
{
    return db_all('SELECT t.* FROM tags t JOIN post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = ? ORDER BY t.name', [$postId]);
}

function related_posts(array $post, int $limit = 3): array
{
    $items = [];
    if (!empty($post['category_id'])) {
        $items = find_posts(['category_id' => $post['category_id'], 'exclude_id' => $post['id'], 'limit' => $limit])['items'];
    }
    if (count($items) < $limit) {
        $ids = array_column($items, 'id');
        foreach (find_posts(['exclude_id' => $post['id'], 'limit' => $limit + count($ids)])['items'] as $candidate) {
            if (count($items) >= $limit) {
                break;
            }
            if (!in_array($candidate['id'], $ids, true)) {
                $items[] = $candidate;
            }
        }
    }
    return $items;
}

function blog_categories(): array
{
    return db_all(
        "SELECT c.*, COUNT(p.id) AS post_count
           FROM categories c
           LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published' AND p.published_at <= ?
          GROUP BY c.id
         HAVING post_count > 0
          ORDER BY c.sort_order, c.name",
        [now()]
    );
}

/** Link a post to tags given as "tag one, tag two", creating tags as needed. */
function sync_post_tags(int $postId, string $csv): void
{
    db_exec('DELETE FROM post_tags WHERE post_id = ?', [$postId]);
    $seen = [];
    foreach (explode(',', $csv) as $name) {
        $name = trim($name);
        $slug = slugify($name);
        if ($slug === '' || isset($seen[$slug])) {
            continue;
        }
        $seen[$slug] = true;
        $tagId = db_val('SELECT id FROM tags WHERE slug = ?', [$slug]);
        if (!$tagId) {
            $tagId = db_insert('tags', ['name' => mb_substr($name, 0, 80), 'slug' => $slug, 'created_at' => now()]);
        }
        db_exec('INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)', [$postId, $tagId]);
    }
}

function active_products(): array
{
    static $products = null;
    if ($products === null) {
        try {
            $products = db_all('SELECT * FROM products WHERE is_active = 1 ORDER BY sort_order, id');
        } catch (PDOException) {
            $products = [];
        }
    }
    return $products;
}

function find_product_by_slug(string $slug): ?array
{
    foreach (active_products() as $product) {
        if ($product['slug'] === $slug) {
            return $product;
        }
    }
    return null;
}

function product_install_url(array $product): string
{
    return $product['install_url'] ?: (string) setting('install_url');
}

/** The product as the chat widget needs it. */
function product_payload(array $product): array
{
    return [
        'id'           => (int) $product['id'],
        'name'         => $product['name'],
        'slug'         => $product['slug'],
        'tagline'      => (string) $product['tagline'],
        'features'     => array_slice(lines($product['features']), 0, 3),
        'metric_value' => (string) $product['metric_value'],
        'metric_label' => (string) $product['metric_label'],
        'install_url'  => product_install_url($product),
        'learn_url'    => $product['learn_more_url'] ? url($product['learn_more_url']) : '',
        'icon'         => icon($product['icon'] ?: 'sparkles'),
        'image'        => $product['image'] ? media_url($product['image']) : '',
    ];
}

function hero_suggestions(string $placement): array
{
    try {
        return db_all(
            'SELECT * FROM suggestions WHERE is_active = 1 AND placement IN (?, ?) ORDER BY sort_order, id',
            [$placement, 'both']
        );
    } catch (PDOException) {
        return [];
    }
}

function featured_case_studies(int $limit = 6): array
{
    return db_all(
        "SELECT * FROM kb_documents
          WHERE is_active = 1 AND is_featured = 1 AND status = 'ready'
          ORDER BY sort_order, id
          LIMIT " . max(1, $limit)
    );
}

function active_rows(string $table): array
{
    return db_all("SELECT * FROM `$table` WHERE is_active = 1 ORDER BY sort_order, id");
}

/** Meta tags for a page, filled in from site settings where the page has none. */
function page_meta(array $meta = []): array
{
    $title = $meta['title'] ?? null;
    return [
        'title'       => $title ? $title . ' · ' . setting('site_name') : (string) setting('seo_title'),
        'description' => $meta['description'] ?? (string) setting('seo_description'),
        'image'       => absolute_url($meta['image'] ?? (string) setting('og_image')),
        'canonical'   => $meta['canonical'] ?? absolute_url(request_path()),
        'noindex'     => !empty($meta['noindex']),
        'type'        => $meta['type'] ?? 'website',
        'schema'      => $meta['schema'] ?? null,
    ];
}
