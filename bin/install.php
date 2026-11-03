<?php
declare(strict_types=1);

/*
 * Sets up BRIX AI: config file, database tables, starter content and the
 * first admin account. Safe to run again: it only fills tables that are empty.
 *
 *   php bin/install.php [--admin-email=you@example.com] [--admin-password=...]
 *                       [--import=C:/xampp/htdocs/thebrix_website] [--no-import]
 *
 * --import copies the case studies, product knowledge and blog posts from the
 * existing BRIX website folder into the knowledge base and blog.
 */

use App\Kb\KnowledgeBase;
use App\Kb\TextExtractor;

if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}

$root = dirname(__DIR__);
$opts = getopt('', ['admin-email:', 'admin-password:', 'admin-name:', 'import:', 'no-import']);

// ---- Config ----------------------------------------------------------------
$configFile = $root . '/config/config.php';
if (!is_file($configFile)) {
    $example = (string) file_get_contents($root . '/config/config.example.php');
    $example = str_replace("'change-me'", "'" . bin2hex(random_bytes(32)) . "'", $example);
    file_put_contents($configFile, $example);
    echo "Created config/config.php (edit it to change database details).\n";
}

$dbConfig = (require $configFile)['db'];
try {
    $server = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $dbConfig['host'], $dbConfig['port']),
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $server->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $dbConfig['name']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
} catch (PDOException $ex) {
    exit('Could not connect to MySQL: ' . $ex->getMessage() . "\nCheck the db section of config/config.php and that MySQL is running.\n");
}

require $root . '/app/bootstrap.php';

foreach (['storage/kb', 'storage/logs', 'public/uploads'] as $dir) {
    if (!is_dir(BASE_PATH . '/' . $dir)) {
        mkdir(BASE_PATH . '/' . $dir, 0775, true);
    }
}

// ---- Schema ----------------------------------------------------------------
$schema = (string) file_get_contents(BASE_PATH . '/database/schema.sql');
foreach (preg_split('/;\s*\R/', $schema) as $statement) {
    if (trim($statement) !== '') {
        db()->exec($statement);
    }
}
echo "Database tables ready.\n";

function table_empty(string $table): bool
{
    return (int) db_val("SELECT COUNT(*) FROM `$table`") === 0;
}

function insert_rows(string $table, array $rows): void
{
    foreach ($rows as $i => $row) {
        db_insert($table, $row + ['sort_order' => $i + 1, 'created_at' => now()]);
    }
}

// ---- Products, plans, testimonials, question tags ---------------------------
$install = static fn (string $campaign): string => 'https://apps.shopify.com/thebrix-io?utm_source=brix-ai&utm_medium=website&utm_campaign=' . $campaign;

if (table_empty('products')) {
    insert_rows('products', [
        [
            'name' => 'Cart Upsell', 'slug' => 'cart-upsell', 'icon' => 'sparkles',
            'tagline' => 'AI-picked add-ons inside the cart drawer',
            'description' => 'Recommends the right extra product at the moment a shopper is about to pay. Picks come from BRIX AI or from your own rules, and show in the cart drawer and on product pages.',
            'features' => "AI or rule-based recommendations\nOne-tap add to cart\nShows in the cart drawer and on product pages",
            'metric_value' => '+23%', 'metric_label' => 'AOV with cart upsells and no extra ad spend',
            'install_url' => $install('cart-upsell'),
            'ai_keywords' => 'upsell, upsells, cross-sell, add-on, recommendation, recommendations, cart, spend more, average order value, aov, one more item, post-purchase',
        ],
        [
            'name' => 'Frequently Bought Together', 'slug' => 'frequently-bought-together', 'icon' => 'link',
            'tagline' => 'Pairings that add the whole set in one tap',
            'description' => 'Pairs products that sell together on product pages and in the cart, Amazon-style. One tap adds the whole combination, so shoppers buy the complete look instead of one item.',
            'features' => "AI or manual product pairings\nOne tap adds the full set\nWorks on product pages and in the cart",
            'metric_value' => '+26%', 'metric_label' => 'AOV with complete-look sets for a fashion brand',
            'install_url' => $install('fbt'),
            'ai_keywords' => 'fbt, frequently bought together, pairing, pairings, complete the look, outfit, related products, cross-sell, set, matching',
        ],
        [
            'name' => 'Rewards Progress Bar', 'slug' => 'rewards-progress-bar', 'icon' => 'gift',
            'tagline' => 'Free shipping and gifts shoppers can see',
            'description' => 'A live bar in the cart shows how close a shopper is to free shipping, a free gift or a discount, so they add one more item to get there. Tiers can be based on spend or item count.',
            'features' => "Spend or item-count milestones\nFree shipping, gift and discount tiers\nConfetti when a tier unlocks",
            'metric_value' => '47%', 'metric_label' => 'of carts unlocked a reward, up from 19%',
            'install_url' => $install('rewards-progress-bar'),
            'ai_keywords' => 'progress bar, free shipping, threshold, free gift, gift, reward, rewards, tier, tiers, milestone, gamification',
        ],
        [
            'name' => 'COD Checkout', 'slug' => 'cod-checkout', 'icon' => 'banknote',
            'tagline' => 'Cash on Delivery without the fake orders',
            'description' => 'Adds a Cash on Delivery button to the cart, product pages and combo pages. A short popup goes from phone number to placed order in a few taps, with OTP checks and a daily limit per phone number to cut fake orders.',
            'features' => "Phone number and OTP verification\nDaily order limit per phone number\nButton on cart, product and combo pages",
            'install_url' => $install('cod'),
            'ai_keywords' => 'cod, cash on delivery, pay on delivery, rto, return to origin, fake orders, otp, india, prepaid',
        ],
        [
            'name' => 'Combo Forge', 'slug' => 'combo-forge', 'icon' => 'layers',
            'tagline' => 'Bundle pages that sell the set, not the item',
            'description' => 'A drag-and-configure builder for bundle pages with four layout styles. Shoppers build a combo or pick a ready-made set, and the saving applies without discount codes.',
            'features' => "Four bundle page layouts\nBuild-your-own boxes and fixed sets\nNo discount codes to manage",
            'metric_value' => '2x', 'metric_label' => 'monthly revenue for an FMCG brand in one month',
            'install_url' => $install('combo-forge'),
            'ai_keywords' => 'bundle, bundles, combo, combos, kit, box, build your own, gift box, set, sets, combo forge',
        ],
        [
            'name' => 'Quantity Packs', 'slug' => 'quantity-packs', 'icon' => 'package',
            'tagline' => '“Buy 2, save 5%” right where shoppers decide',
            'description' => 'Shows quantity offers as simple cards on the product page. Shoppers pick a pack, choose a size or colour for each item, and the saving applies at checkout on its own.',
            'features' => "Up to six offers per product\nMix sizes and colours in one pack\nSaving applied automatically at checkout",
            'metric_value' => '+41%', 'metric_label' => 'AOV for a skincare brand with tiered offers',
            'install_url' => $install('quantity-packs'),
            'ai_keywords' => 'quantity, volume discount, quantity discount, pack, packs, buy 2, buy more save more, tiered offer, tiered offers, multi-buy, bulk',
        ],
    ]);
    echo "Added 6 products.\n";
}

if (table_empty('pricing_plans')) {
    insert_rows('pricing_plans', [
        [
            'name' => 'Free', 'tagline' => 'Launch and learn', 'price_monthly' => 0, 'price_yearly' => null,
            'features' => "50 orders a month included, then \$0.10 per order\n10 Brix AI credits a month\nCart Drawer, Announcement Bar and Empty Cart live\nPreview every premium feature before you pay",
            'cta_label' => 'Install free', 'cta_url' => $install('pricing-free'),
        ],
        [
            'name' => 'Starter', 'tagline' => 'Grow your AOV', 'price_monthly' => 29, 'price_yearly' => 290, 'badge' => 'Most popular', 'is_highlighted' => 1,
            'features' => "500 orders a month included, then \$0.30 per order\n30 Brix AI credits a month\nFBT, Progress Bar, Coupon Banner and Cart Upsell live\nUp to 3 Combo Forge bundles\nFull analytics, no watermark\nPriority email and 24/7 AI support",
            'cta_label' => 'Start 14-day free trial', 'cta_url' => $install('pricing-starter'),
        ],
        [
            'name' => 'Pro', 'tagline' => 'High-volume brands', 'price_monthly' => 79, 'price_yearly' => 790,
            'features' => "Unlimited orders, no overage\n90 Brix AI credits a month\nUnlimited Combo Forge bundles\nAI Analytics and Advanced AI Analytics\nEverything in Starter",
            'cta_label' => 'Start 14-day free trial', 'cta_url' => $install('pricing-pro'),
        ],
    ]);
    echo "Added 3 pricing plans.\n";
}

if (table_empty('testimonials')) {
    insert_rows('testimonials', [
        ['name' => 'Sana Rahim', 'metric_value' => '+41%', 'metric_label' => 'AOV', 'quote' => 'Brix helped us turn our cart into a real sales channel. The upsell and rewards bar features made it easier for customers to add more before checkout.'],
        ['name' => 'Daniel Kim', 'metric_value' => '+28%', 'metric_label' => 'AOV', 'quote' => 'Frequently Bought Together and the coupon slider solved two major problems for us. Customers could discover related products and apply discounts without leaving the cart.'],
        ['name' => 'Amara Obi', 'metric_value' => '+35%', 'metric_label' => 'AOV', 'quote' => 'Brix AI Analysis gave us clarity on what was working. We stopped guessing and started optimizing our cart based on real opportunities.'],
        ['name' => 'Marco Tan', 'metric_value' => '+52%', 'metric_label' => 'AOV', 'quote' => 'Setup was simple, customization was clean, and the cart experience felt premium. Brix is a strong AOV booster for Shopify brands.'],
        ['name' => 'Lena Petrov', 'metric_value' => '+23%', 'metric_label' => 'AOV', 'quote' => 'The insight cards are like having a CRO consultant on retainer, except it costs $29 a month.'],
    ]);
    echo "Added 5 testimonials.\n";
}

if (table_empty('suggestions')) {
    insert_rows('suggestions', [
        ['label' => 'How do I increase my AOV?', 'prompt' => 'How can I increase the average order value of my Shopify store?'],
        ['label' => 'Grow revenue without more ads', 'prompt' => 'How can I grow revenue without increasing my ad spend?'],
        ['label' => 'Set a free-shipping threshold', 'prompt' => 'How should I set a free-shipping threshold to raise my AOV?'],
        ['label' => 'Bundles or FBT?', 'prompt' => 'Should I use bundles or Frequently Bought Together for my store?'],
        ['label' => 'Do upsells hurt conversion?', 'prompt' => 'Will cart upsells hurt my conversion rate?'],
        ['label' => 'Cut fake COD orders', 'prompt' => 'How do I reduce fake cash-on-delivery orders?'],
    ]);
    echo "Added 6 question tags.\n";
}

// ---- Import from the existing BRIX website -------------------------------------
$source = rtrim(str_replace('\\', '/', $opts['import'] ?? 'C:/xampp/htdocs/thebrix_website'), '/');
$import = !isset($opts['no-import']) && is_dir($source);

function front_matter(string $raw): array
{
    $raw = (string) preg_replace('/\A\xEF\xBB\xBF/', '', $raw);
    if (!preg_match('/\A---\R(.*?)\R---\R(.*)\z/s', $raw, $m)) {
        return [[], $raw];
    }
    $meta = [];
    foreach (preg_split('/\R/', $m[1]) as $line) {
        if (preg_match('/^([a-z_]+):\s*(.*)$/i', $line, $kv)) {
            $meta[$kv[1]] = trim(trim($kv[2]), '"\'');
        }
    }
    return [$meta, $m[2]];
}

function first_heading(string $md, string $fallback): string
{
    return preg_match('/^#\s+(.+)$/m', $md, $m) ? trim($m[1], " #*\"") : $fallback;
}

/** Copy a source file into storage/kb so admins can download the original. */
function store_copy(string $file): array
{
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    copy($file, KnowledgeBase::storageDir() . '/' . $name);
    return ['name' => $name, 'original' => basename($file), 'mime' => $ext === 'pdf' ? 'application/pdf' : 'text/markdown', 'size' => (int) filesize($file)];
}

function category_id(string $name): int
{
    $slug = slugify($name);
    $id = db_val('SELECT id FROM categories WHERE slug = ?', [$slug]);
    return (int) ($id ?: db_insert('categories', ['name' => $name, 'slug' => $slug, 'created_at' => now()]));
}

$productIds = array_column(db_all('SELECT id, slug FROM products'), 'id', 'slug');
$postUrls = [];

if ($import && table_empty('posts')) {
    $parsedown = new Parsedown();
    $files = glob($source . '/content/migrated/*.md') ?: [];
    $skincare = glob($source . '/ai assets/*Skincare*.md') ?: [];
    foreach (array_merge($files, $skincare) as $file) {
        [$meta, $body] = front_matter((string) file_get_contents($file));
        $isCase = ($meta['type'] ?? '') === 'case_study' || str_contains(basename($file), 'Case_Study');
        $title = $meta['title'] ?? first_heading($body, pathinfo($file, PATHINFO_FILENAME));
        $body = (string) preg_replace('/\A\s*#\s+[^\n]*\n/', '', $body, 1);
        $html = $parsedown->text(str_replace('\\.', '.', $body));
        $slug = isset($meta['slug']) ? (string) preg_replace('/^blog-/', '', $meta['slug']) : 'case-study-skincare-tiered-offers';
        $excerpt = $meta['excerpt'] ?? ($meta['hero_subtitle'] ?? 'A skincare brand lifted AOV from about ₹880 to ₹1,240 in six weeks with tiered offers and post-purchase upsells.');
        $date = $meta['date'] ?? '2026-08-26';

        $postId = db_insert('posts', [
            'title'            => $title,
            'slug'             => unique_slug('posts', $slug),
            'excerpt'          => $excerpt,
            'content'          => $html,
            'category_id'      => category_id($isCase ? 'Case Studies' : ($meta['category'] ?? 'Guides')),
            'status'           => 'published',
            'published_at'     => date('Y-m-d 09:00:00', (int) strtotime($date)),
            'meta_description' => str_limit($excerpt, 158),
            'reading_minutes'  => (int) ($meta['read_minutes'] ?? reading_minutes($html)),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
        $tags = $isCase ? ($meta['category'] ?? 'Skincare') . ', AOV' : 'AOV';
        sync_post_tags($postId, $tags);
        $postUrls[basename($file)] = '/blog/' . db_val('SELECT slug FROM posts WHERE id = ?', [$postId]);
    }
    echo 'Imported ' . count($postUrls) . " blog posts and case studies into the blog.\n";
}

if ($import && table_empty('kb_documents')) {
    $count = 0;
    $add = static function (string $file, array $meta, string $text) use (&$count): void {
        KnowledgeBase::create($meta + ['is_active' => 1], $text, store_copy($file));
        $count++;
    };

    // Case studies, with the card details BRIX AI shows under its answers.
    $cases = [
        'case-study-aov-without-ad-spend.md' => ['industry' => 'Shopify D2C', 'result_value' => '+23%', 'result_label' => 'AOV, with ROAS up from 3.8x to 4.7x', 'product' => 'cart-upsell'],
        'case-study-complete-look-recommendations.md' => ['industry' => 'Fashion & apparel', 'result_value' => '+26%', 'result_label' => 'AOV, cross-sell clicks up from 9% to 27%', 'product' => 'frequently-bought-together'],
        'case-study-fmcg-aov-without-ad-spend.md' => ['industry' => 'FMCG', 'result_value' => '2x', 'result_label' => 'monthly revenue (₹7 lakh to ₹14 lakh) in one month', 'product' => 'combo-forge'],
        'case-study-rewards-progress-bar.md' => ['industry' => 'Shopify D2C', 'result_value' => '47%', 'result_label' => 'of carts unlocked a reward, up from 19%', 'product' => 'rewards-progress-bar'],
    ];
    $order = 1;
    foreach ($cases as $name => $info) {
        $file = $source . '/content/migrated/' . $name;
        if (!is_file($file)) {
            continue;
        }
        [$meta, $body] = front_matter((string) file_get_contents($file));
        $add($file, [
            'title'        => $meta['title'] ?? first_heading($body, $name),
            'doc_type'     => 'case_study',
            'industry'     => $info['industry'],
            'result_value' => $info['result_value'],
            'result_label' => $info['result_label'],
            'summary'      => $meta['excerpt'] ?? null,
            'product_id'   => $productIds[$info['product']] ?? null,
            'public_url'   => $postUrls[$name] ?? null,
            'is_featured'  => 1,
            'sort_order'   => $order++,
        ], TextExtractor::markdown($body));
    }
    foreach (glob($source . '/ai assets/*Skincare*.md') ?: [] as $file) {
        $text = (string) file_get_contents($file);
        $add($file, [
            'title'        => first_heading($text, 'Skincare case study'),
            'doc_type'     => 'case_study',
            'brand'        => 'D2C skincare brand',
            'industry'     => 'Skincare',
            'result_value' => '+41%',
            'result_label' => 'AOV in six weeks (₹880 to ₹1,240)',
            'summary'      => 'Tiered quantity offers and post-purchase upsells moved average order value from about ₹880 to ₹1,240 and items per order from 1.3 to 1.8 in roughly six weeks.',
            'product_id'   => $productIds['quantity-packs'] ?? null,
            'public_url'   => $postUrls[basename($file)] ?? null,
            'is_featured'  => 1,
            'sort_order'   => $order++,
        ], TextExtractor::markdown($text));
    }

    // Guides (the blog articles).
    foreach (glob($source . '/content/migrated/blog-*.md') ?: [] as $file) {
        [$meta, $body] = front_matter((string) file_get_contents($file));
        $add($file, [
            'title'      => $meta['title'] ?? first_heading($body, basename($file)),
            'doc_type'   => 'guide',
            'summary'    => $meta['excerpt'] ?? null,
            'public_url' => $postUrls[basename($file)] ?? null,
        ], TextExtractor::markdown($body));
    }

    // Product knowledge used by the old site's assistant.
    $types = ['faq.md' => 'faq', 'how-to.md' => 'guide', 'setup-guides.md' => 'guide', 'troubleshooting.md' => 'guide', 'best-practices.md' => 'guide'];
    $products = ['rewards-progress-bar.md' => 'rewards-progress-bar', 'combo-forge.md' => 'combo-forge', 'ai-upsell.md' => 'cart-upsell'];
    foreach (glob($source . '/knowledge/*.md') ?: [] as $file) {
        $name = basename($file);
        if (preg_match('/^blog\d+\.md$/', $name) || $name === 'case-studies.md') {
            continue; // same articles as the blog posts and case studies above
        }
        $text = (string) file_get_contents($file);
        $add($file, [
            'title'      => first_heading($text, ucfirst(str_replace('-', ' ', pathinfo($name, PATHINFO_FILENAME)))),
            'doc_type'   => $types[$name] ?? 'product',
            'product_id' => isset($products[$name]) ? ($productIds[$products[$name]] ?? null) : null,
        ], TextExtractor::markdown($text));
    }
    echo "Indexed $count documents into the knowledge base.\n";
}

if ($import && table_empty('faqs') && is_file($source . '/knowledge/faq.md')) {
    $faq = (string) file_get_contents($source . '/knowledge/faq.md');
    preg_match_all('/\*\*Q:\s*(.+?)\*\*\s*\R+A:\s*(.+?)(?=\R\s*\R|\R\*\*Q:|\z)/s', $faq, $m, PREG_SET_ORDER);
    $rows = [];
    foreach (array_slice($m, 0, 8) as $qa) {
        $rows[] = ['question' => trim($qa[1]), 'answer' => trim((string) preg_replace('/\s+/', ' ', $qa[2]))];
    }
    insert_rows('faqs', $rows);
    echo 'Added ' . count($rows) . " FAQs.\n";
}

if ($import && table_empty('pages') && is_file($source . '/knowledge/overview.md')) {
    $overview = (string) file_get_contents($source . '/knowledge/overview.md');
    $overview = (string) preg_replace('/\A\s*#\s+[^\n]*\n/', '', $overview, 1);
    db_insert('pages', [
        'title'            => 'About BRIX',
        'slug'             => 'about',
        'content'          => (new Parsedown())->text($overview),
        'meta_description' => 'BRIX makes the Shopify cart persuasive, with rewards, recommendations, bundles and coupons, without writing code.',
        'status'           => 'published',
        'show_in_footer'   => 1,
        'published_at'     => now(),
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);
    echo "Added the About page at /about.\n";
}

if (!$import && !isset($opts['no-import'])) {
    echo "Skipped importing content: $source was not found. Pass --import=<folder> to use another folder.\n";
}

// ---- First admin -----------------------------------------------------------------
if (table_empty('users')) {
    $email = mb_strtolower($opts['admin-email'] ?? 'admin@thebrix.io');
    $password = $opts['admin-password'] ?? substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(18))), 0, 16);
    if ($problem = password_problem($password)) {
        exit($problem . "\n");
    }
    db_insert('users', [
        'name'          => $opts['admin-name'] ?? 'Admin',
        'email'         => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role'          => 'admin',
        'created_at'    => now(),
    ]);
    echo "\nAdmin account created\n  Email:    $email\n  Password: $password\nChange the password after signing in (My account).\n";
}

echo "\nDone. Start the site with serve.bat and open http://localhost:8000 (admin: /admin).\n";
