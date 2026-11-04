<?php
declare(strict_types=1);

/**
 * One-off content updates for sites whose database already exists.
 *
 * Homepage text, products, chat suggestions and FAQs live in the database, so a
 * code deploy alone cannot change them. Each revision runs once (tracked by the
 * content_revision setting) on the first request after a deploy, and it only
 * replaces text that still matches the old default, so admin edits are kept.
 */
const CONTENT_REVISION = 2;

/** Runs any pending revisions in order. Returns true when settings may have changed. */
function content_upgrade(array $stored): bool
{
    $from = (int) ($stored['content_revision'] ?? 0);
    if ($from >= CONTENT_REVISION) {
        return false;
    }
    try {
        // Claim the revision first so two requests arriving together do not both run it.
        $claimed = db_exec(
            'INSERT INTO settings (`key`, `value`, updated_at) VALUES (\'content_revision\', ?, ?)
             ON DUPLICATE KEY UPDATE
                updated_at = IF(CAST(`value` AS UNSIGNED) < VALUES(`value`), VALUES(updated_at), updated_at),
                `value` = IF(CAST(`value` AS UNSIGNED) < VALUES(`value`), VALUES(`value`), `value`)',
            [(string) CONTENT_REVISION, now()]
        );
        if ($claimed === 0) {
            return false;
        }
        for ($revision = $from + 1; $revision <= CONTENT_REVISION; $revision++) {
            ("content_revision_$revision")($stored);
        }
    } catch (Throwable $ex) {
        // The revision stays claimed, so this is logged once and never retried in a loop.
        log_message('app', 'Content upgrade failed: ' . $ex->getMessage());
        return false;
    }
    return true;
}

/** Revision 1: the site covers all three BRIX apps, not only Brix Convert. */
function content_revision_1(array $stored): void
{
    // Settings still holding the old Convert-only default go back to the new default.
    $old = [
        'site_tagline'       => 'AI-powered AOV growth for Shopify',
        'footer_text'        => 'BRIX helps Shopify brands raise average order value with carts that sell: upsells, bundles, reward tiers and COD in one app.',
        'seo_title'          => 'BRIX AI: ask how to grow your Shopify AOV',
        'seo_description'    => 'Ask BRIX AI how to increase average order value. Answers come from real BRIX case studies on upsells, bundles, free-shipping bars and COD.',
        'hero_subtitle'      => 'Ask how to grow your Shopify average order value. Every answer comes from real BRIX case studies.',
        'chat_placeholder'   => 'Ask how to increase your average order value…',
        'products_title'     => 'Six apps. One goal: bigger carts.',
        'chat_welcome_text'  => 'Ask about upsells, bundles, free-shipping bars, COD or your AOV. I answer from real BRIX case studies.',
        'ai_off_topic_reply' => "I'm BRIX AI, so I stick to growing Shopify stores: average order value, upsells, bundles, free-shipping bars, COD and the BRIX apps. Ask me anything about those and I'll answer from real BRIX case studies.",
        'kb_synonyms'        => "aov => average order value\nfbt => frequently bought together\ncod => cash on delivery\nrto => cash on delivery returns fake orders\nupsell => upsells cross-sell recommendations add-on\ncross-sell => upsell recommendations frequently bought together\nbundle => bundles combo forge\ncombo => combo forge bundles\nprogress bar => rewards progress bar free shipping gift tiers\nfree shipping => free shipping threshold rewards progress bar\ngift => free gift rewards progress bar\ncoupon => coupon slider discount codes\ndiscount => coupon slider discount offers\nspend more => average order value upsell bundles\nbasket => average order value cart\norder value => average order value\nads => ad spend roas\nroas => return on ad spend\nabandon => cart abandonment cart drawer\nskincare => skincare beauty\nfashion => fashion apparel complete the look",
        'home_how_steps'     => '[{"title":"Ask in plain words","text":"Tell BRIX AI what you want to fix: small orders, single-item carts, abandoned checkouts or COD drop-offs."},{"title":"Get proof, not guesses","text":"Answers cite real BRIX case studies with the numbers: what the brand changed and what moved."},{"title":"Install and switch it on","text":"BRIX AI points you to the app that does the job. Install it from the Shopify App Store and go live without code."}]',
        'home_why'           => '[{"title":"No code, no theme edits","text":"Turn on the app embed and configure every feature from the BRIX admin."},{"title":"Preview before you publish","text":"Every change shows in a live preview, so nothing goes live by accident."},{"title":"Built for Online Store 2.0","text":"Works with modern Shopify themes through app embeds."},{"title":"AI that writes the first draft","text":"Brix AI drafts bundle copy, upsell rules and promotions, and you approve what ships."},{"title":"Start free, upgrade on results","text":"The Free plan never expires. Starter and Pro start with a 14-day trial, billed through Shopify."},{"title":"Support that answers","text":"Priority email support and 24/7 AI support on paid plans."}]',
        'home_why_subtitle'  => 'Everything is set up from settings panels with a live preview. No theme edits, no code.',
        'home_blog_title'    => 'Guides on raising Shopify AOV',
        'home_cta_title'     => 'Ready to turn your cart into a growth engine?',
        'home_cta_text'      => 'Install BRIX free, or ask BRIX AI what to fix first.',
    ];
    $same = static function (string $a, string $b): bool {
        $a = trim(str_replace("\r\n", "\n", $a));
        $b = trim(str_replace("\r\n", "\n", $b));
        if ($a === $b) {
            return true;
        }
        $ja = json_decode($a, true);
        return is_array($ja) && $ja === json_decode($b, true);
    };
    foreach ($old as $key => $value) {
        if (isset($stored[$key]) && $same((string) $stored[$key], $value)) {
            db_exec('DELETE FROM settings WHERE `key` = ?', [$key]);
        }
    }

    // Each app gets its own App Store link and feature list, unless one is already set.
    $apps = [
        'brix-try-on'  => ['install_url' => 'https://apps.shopify.com/fitsnap?st_source=autocomplete&surface_detail=autocomplete_apps', 'features' => "AI virtual try-on on product pages\nOn-model photos without a photoshoot\nEvery try-on captures a lead"],
        'brix-convert' => ['install_url' => null, 'features' => "AI cart upsells and Frequently Bought Together\nBundles, quantity packs and a rewards bar\nCOD button and coupon slider"],
        'brix-connect' => ['install_url' => 'https://apps.shopify.com/push-nova?st_source=autocomplete&surface_detail=autocomplete_apps', 'features' => "One-click web push opt-in\nAbandoned cart and checkout reminders\nSegments by behaviour, device and location"],
    ];
    foreach ($apps as $slug => $values) {
        if ($values['install_url'] !== null) {
            db_exec("UPDATE products SET install_url = ? WHERE slug = ? AND (install_url IS NULL OR install_url = '')", [$values['install_url'], $slug]);
        }
        db_exec("UPDATE products SET features = ? WHERE slug = ? AND (features IS NULL OR features = '')", [$values['features'], $slug]);
    }

    // Question chips for the other two apps, next to the existing AOV ones.
    $suggestions = [
        ['Which BRIX app do I need?', 'Which BRIX app should I start with: Try On, Convert or Connect?', 0],
        ['Cut returns with try-on', 'Can virtual try-on reduce returns and raise conversion on my Shopify store?', 1],
        ['Win back cart abandoners', 'How do I bring back shoppers who left without buying?', 2],
    ];
    foreach ($suggestions as [$label, $prompt, $order]) {
        if (!db_val('SELECT id FROM suggestions WHERE label = ?', [$label])) {
            db_insert('suggestions', ['label' => $label, 'prompt' => $prompt, 'placement' => 'both', 'sort_order' => $order, 'is_active' => 1, 'created_at' => now()]);
        }
    }

    $faqs = [
        ['Which BRIX app should I start with?', BRIX_FAQ_START],
        ['Can I use all three BRIX apps together?', BRIX_FAQ_TOGETHER],
    ];
    foreach ($faqs as $i => [$question, $answer]) {
        if (!db_val('SELECT id FROM faqs WHERE question = ?', [$question])) {
            db_insert('faqs', ['question' => $question, 'answer' => $answer, 'is_active' => 1, 'sort_order' => $i - 2, 'created_at' => now()]);
        }
    }
}

const BRIX_FAQ_START = 'Start where your store loses the most sales. If orders are small or carts hold one item, start with BRIX Convert. If shoppers hesitate or send items back because of fit or look, start with BRIX Try On. If visitors leave and never come back, start with BRIX Connect. Not sure? Ask BRIX AI and it will suggest one.';
const BRIX_FAQ_TOGETHER = 'Yes. Each app installs separately from the Shopify App Store and handles a different job: Convert grows the order, Try On gives shoppers the confidence to buy, and Connect brings them back after they leave. Each one has a free plan, so you can start with one and add the others later.';

/** Revision 2: Brix Convert comes first, then Try On, then Connect. */
function content_revision_2(array $stored): void
{
    // Products drive the hero cards and the footer. Swap only if they still sit in revision 1's order.
    $order = db_all("SELECT slug, sort_order FROM products WHERE slug IN ('brix-try-on', 'brix-convert')");
    $sort = array_column($order, 'sort_order', 'slug');
    if (isset($sort['brix-try-on'], $sort['brix-convert']) && (int) $sort['brix-try-on'] < (int) $sort['brix-convert']) {
        db_exec('UPDATE products SET sort_order = ? WHERE slug = ?', [(int) $sort['brix-try-on'], 'brix-convert']);
        db_exec('UPDATE products SET sort_order = ? WHERE slug = ?', [(int) $sort['brix-convert'], 'brix-try-on']);
    }

    // If the homepage apps were saved from the admin with Try On ahead of Convert, put Convert first.
    $rows = json_decode((string) ($stored['home_apps'] ?? ''), true);
    if (is_array($rows)) {
        $slugs = array_column($rows, 'product');
        $try = array_search('brix-try-on', $slugs, true);
        $convert = array_search('brix-convert', $slugs, true);
        if ($try !== false && $convert !== false && $try < $convert) {
            [$rows[$try], $rows[$convert]] = [$rows[$convert], $rows[$try]];
            db_exec('UPDATE settings SET `value` = ?, updated_at = ? WHERE `key` = ?', [json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), now(), 'home_apps']);
        }
    }

    // FAQs added by revision 1 before the reorder, with Try On named first.
    db_exec('UPDATE faqs SET answer = ? WHERE question = ? AND answer LIKE ?', [BRIX_FAQ_START, 'Which BRIX app should I start with?', 'Start where your store loses the most sales. If shoppers hesitate%']);
    db_exec('UPDATE faqs SET answer = ? WHERE question = ? AND answer LIKE ?', [BRIX_FAQ_TOGETHER, 'Can I use all three BRIX apps together?', '%Try On before the shopper buys, Convert while they shop%']);
}
