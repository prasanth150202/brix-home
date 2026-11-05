<?php
declare(strict_types=1);

/**
 * Every setting the admin can change, with the value used until it is saved.
 * Lists that admins edit row by row (stats, steps, features) are stored as JSON.
 */
function setting_defaults(): array
{
    $json = static fn (array $rows): string => json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return [
        // Site
        'site_name'        => 'BRIX',
        'site_tagline'     => 'Shopify apps for bigger orders, virtual try-on and win-back',
        'site_logo'        => 'assets/img/brix-logo-light.png',
        'site_mark'        => 'assets/img/brix-mark-light.png',
        'site_favicon'     => 'assets/img/favicon.png',
        'contact_email'    => 'support@thebrix.io',
        'install_url'      => 'https://apps.shopify.com/thebrix-io?utm_source=brix-ai&utm_medium=website',
        'demo_url'         => '',
        'social_x'         => '',
        'social_linkedin'  => '',
        'social_instagram' => '',
        'social_youtube'   => '',
        'footer_text'      => 'BRIX makes Shopify apps that grow every part of the sale: Convert for bigger orders, Try On for confident buyers and fewer returns, and Connect to bring shoppers back.',
        'seo_title'        => 'BRIX: Shopify apps for bigger orders, virtual try-on and web push',
        'seo_description'  => 'BRIX makes three Shopify apps: Convert for upsells, bundles and COD, Try On for AI virtual try-on, and Connect for web push that brings shoppers back. Ask BRIX AI where to start.',
        'og_image'         => 'assets/img/og-brix.png',
        'currency_symbol'  => '$',
        'head_code'        => '',
        'body_code'        => '',

        // Hero and chat
        'hero_eyebrow'       => 'Ask BRIX AI',
        'hero_title'         => 'BRIX AI',
        'hero_subtitle'      => 'Ask how to grow your Shopify store: bigger orders, more confident shoppers, more of them coming back. Every answer comes from real BRIX case studies.',
        'chat_placeholder'   => 'Ask how to grow your Shopify store…',
        'chat_hint'          => 'Answers from real BRIX case studies',
        'products_title'     => 'Three apps. One goal: a store that grows.',
        'chat_welcome_title' => 'What do you want to grow today?',
        'chat_welcome_text'  => 'Ask about virtual try-on, upsells, bundles, free-shipping bars, COD, push notifications or your AOV. I answer from real BRIX case studies.',
        'chat_disclaimer'    => 'BRIX AI can make mistakes. Results vary by store.',
        // AI
        'ai_provider'               => 'anthropic',
        'ai_assistant_name'         => 'BRIX AI',
        'anthropic_api_key'         => '',
        'anthropic_model'           => 'claude-opus-5-5',
        'anthropic_effort'          => 'low',
        'openai_api_key'            => '',
        'openai_model'              => 'gpt-5-mini',
        'openai_temperature'        => '0.3',
        'openrouter_api_key'        => '',
        'openrouter_model'          => 'google/gemma-4-26b-a4b-it:free',
        'openrouter_fallback_model' => 'openai/gpt-oss-20b:free',
        'openrouter_temperature'    => '0.3',
        'ai_max_tokens'             => '4000',
        'ai_context_chunks'         => '6',
        'ai_history_messages'       => '6',
        'ai_max_question_chars'     => '1000',
        'ai_rate_limit_per_hour'    => '30',
        'ai_extra_instructions'     => '',
        'ai_off_topic_reply'        => "I'm BRIX AI, so I stick to growing Shopify stores: virtual try-on and returns, average order value, upsells, bundles, free-shipping bars, COD, push notifications and the BRIX apps: Convert, Try On and Connect. Ask me anything about those and I'll answer from real BRIX case studies.",
        'ai_unavailable_reply'      => 'BRIX AI is taking a short break. Please try again in a minute, or email support@thebrix.io and the team will help.',
        'kb_synonyms'               => implode("\n", [
            'aov => average order value',
            'fbt => frequently bought together',
            'cod => cash on delivery',
            'rto => cash on delivery returns fake orders',
            'upsell => upsells cross-sell recommendations add-on',
            'cross-sell => upsell recommendations frequently bought together',
            'bundle => bundles combo forge',
            'combo => combo forge bundles',
            'progress bar => rewards progress bar free shipping gift tiers',
            'free shipping => free shipping threshold rewards progress bar',
            'gift => free gift rewards progress bar',
            'coupon => coupon slider discount codes',
            'discount => coupon slider discount offers',
            'spend more => average order value upsell bundles',
            'basket => average order value cart',
            'order value => average order value',
            'ads => ad spend roas',
            'roas => return on ad spend',
            'abandon => cart abandonment cart drawer brix connect web push',
            'try on => brix try on virtual try-on',
            'try-on => brix try on virtual try-on',
            'returns => brix try on virtual try-on fit',
            'model photos => brix try on ai studio on-model images',
            'push => brix connect web push notifications',
            'notification => brix connect web push notifications',
            'win back => brix connect web push abandoned cart recovery',
            'retention => brix connect repeat customers web push',
            'skincare => skincare beauty',
            'fashion => fashion apparel complete the look',
        ]),

        // Homepage sections
        'home_brands_enabled' => '1',
        'home_brands' => $json([
            ['name' => 'House of KO', 'logo' => 'assets/img/brands/house-of-ko.png'],
            ['name' => 'Elther', 'logo' => 'assets/img/brands/elther.png'],
            ['name' => 'Tru Foods', 'logo' => 'assets/img/brands/tru-foods.png'],
            ['name' => 'Tirls', 'logo' => 'assets/img/brands/tirls.png'],
            ['name' => 'Core Eat', 'logo' => 'assets/img/brands/core-eat.png'],
            ['name' => 'Ulag Natural', 'logo' => 'assets/img/brands/ulag-natural.png'],
            ['name' => 'Aadhya Herbal Care', 'logo' => 'assets/img/brands/aadhya.png'],
            ['name' => 'SeedTot', 'logo' => 'assets/img/brands/seedtot.png'],
        ]),

        'home_stats_enabled' => '1',
        'home_stats' => $json([
            ['value' => '+41%', 'label' => 'AOV for a skincare brand in six weeks'],
            ['value' => '2x', 'label' => 'monthly revenue for an FMCG brand on the same ad budget'],
            ['value' => '3x', 'label' => 'cross-sell clicks with complete-look sets'],
            ['value' => '47%', 'label' => 'of carts unlocked a reward, up from 19%'],
        ]),

        // The BRIX apps, one row per app in the order a shopper meets them.
        // Shown as the journey strip and as one showcase per app.
        'home_apps_enabled'  => '1',
        'home_apps_eyebrow'  => 'The BRIX apps',
        'home_apps_title'    => 'Three apps. Three ways to grow.',
        'home_apps_subtitle' => 'Stores lose sales in three places: carts that stay small, shoppers who are not sure, and visitors who never come back. Each BRIX app fixes one of them. Use one, or run all three together.',
        'home_apps' => $json([
            [
                'product' => 'brix-convert',
                'stage'   => 'Bigger orders',
                'problem' => 'Shoppers add one item and check out, when the cart could have held two or three.',
                'title'   => 'Turn one-item carts into bigger orders',
                'text'    => 'BRIX Convert sets up cart upsells, Frequently Bought Together, bundles, quantity packs, a rewards bar and a coupon slider from one prompt. Tap the suggestions in this cart to watch an order grow.',
                'points'  => "AI cart upsells and Frequently Bought Together\nBundles and quantity packs, no code\nRewards bar for free shipping and free gifts\nCash on Delivery button and coupon slider",
                'visual'  => 'convert',
            ],
            [
                'product' => 'brix-try-on',
                'stage'   => 'Fewer returns',
                'problem' => 'Shoppers can’t tell how it will look on them, so they leave, or buy and send it back.',
                'title'   => 'Let shoppers see it on themselves before they buy',
                'text'    => 'BRIX Try On adds AI virtual try-on to your product pages. A shopper uploads a photo, sees themselves wearing the product, and adds it to cart from the same screen.',
                'points'  => "AI try-on button on your product pages\nOn-model photos from flat-lay or mannequin shots, no photoshoot\nEvery try-on captures an email and phone number as a lead\nAnalytics for try-ons, conversions and revenue",
                'visual'  => 'tryon',
            ],
            [
                'product' => 'brix-connect',
                'stage'   => 'Repeat buyers',
                'problem' => 'Most visitors leave without buying, and there is no easy way to bring them back.',
                'title'   => 'Bring shoppers back with web push',
                'text'    => 'BRIX Connect sends web push notifications that recover abandoned carts and announce sales and new products. Visitors subscribe in one click, with no email or phone number needed.',
                'points'  => "One-click opt-in, no email or phone number\nAutomatic abandoned cart and checkout reminders\nFlash sales, product launches and order updates\nSegments by behaviour, device, location and cart",
                'visual'  => 'connect',
            ],
        ]),

        'home_how_enabled'  => '1',
        'home_how_eyebrow'  => 'How BRIX AI works',
        'home_how_title'    => 'From question to playbook in seconds',
        'home_how_subtitle' => 'BRIX AI reads our library of real merchant case studies and turns them into advice for your store.',
        'home_how_steps' => $json([
            ['title' => 'Ask in plain words', 'text' => 'Tell BRIX AI what you want to fix: returns, small orders, single-item carts, COD drop-offs or shoppers who never come back.'],
            ['title' => 'Get proof, not guesses', 'text' => 'Answers cite real BRIX case studies with the numbers: what the brand changed and what moved.'],
            ['title' => 'Install and switch it on', 'text' => 'BRIX AI points you to the BRIX app that does the job: Convert, Try On or Connect. Install it from the Shopify App Store and go live without code.'],
        ]),

        'home_demo_enabled'  => '1',
        'home_demo_eyebrow'  => 'Live demo',
        'home_demo_title'    => 'Watch an order grow',
        'home_demo_subtitle' => 'Tap the suggestions in this cart. Each nudge is a BRIX app doing its job, and the order value climbs with it.',

        'home_features_enabled'  => '1',
        'home_features_eyebrow'  => 'Everything in the box',
        'home_features_title'    => 'One app. Every nudge that grows a cart.',
        'home_features_subtitle' => 'Cart drawer, rewards, upsells, coupons, bundles, COD and analytics, configured from one admin with a live preview.',
        'home_features' => $json([
            ['icon' => 'cart', 'size' => 'wide', 'title' => 'A cart drawer that earns its keep', 'text' => 'A slide-out cart with announcements, an empty-cart state and a checkout button styled to your brand, with a live preview before anything goes live.'],
            ['icon' => 'gift', 'size' => 'normal', 'title' => 'Reward tiers shoppers can see', 'text' => 'Spend or item-count milestones unlock free shipping and gifts right inside the cart.'],
            ['icon' => 'sparkles', 'size' => 'normal', 'title' => 'Upsells that fit the cart', 'text' => 'AI-picked or rule-based recommendations in the cart and on product pages.'],
            ['icon' => 'ticket', 'size' => 'normal', 'title' => 'Coupons inside the cart', 'text' => 'Active discount codes, one tap to apply, so nobody leaves to hunt for a code.'],
            ['icon' => 'banknote', 'size' => 'normal', 'title' => 'Cash on Delivery, minus the drop-off', 'text' => 'Phone number to placed order in a few taps, with OTP checks to cut fake orders.'],
            ['icon' => 'chart', 'size' => 'full', 'title' => 'Attribution down to the nudge', 'text' => 'See which tier, upsell or bundle earned each extra dollar, plus AI insight cards that tell you what to change next.'],
        ]),

        'home_results_enabled'  => '1',
        'home_results_eyebrow'  => 'Case studies',
        'home_results_title'    => 'Real stores. Real numbers.',
        'home_results_subtitle' => 'Every BRIX AI answer is grounded in case studies like these.',

        'home_testimonials_enabled' => '1',
        'home_testimonials_eyebrow' => 'Merchants',
        'home_testimonials_title'   => 'What merchants say about BRIX',

        'home_why_enabled'  => '1',
        'home_why_eyebrow'  => 'Why BRIX',
        'home_why_title'    => 'Built for merchants, not developers',
        'home_why_subtitle' => 'Every BRIX app is set up from settings panels with a live preview. No theme edits, no code.',
        'home_why' => $json([
            ['title' => 'No code, no theme edits', 'text' => 'Every BRIX app runs through a Shopify app embed. Turn it on and set everything up from the app’s own admin.'],
            ['title' => 'Preview before you publish', 'text' => 'Changes show in a live preview first, so nothing goes live on your store by accident.'],
            ['title' => 'AI that does the setup', 'text' => 'Convert builds your offers from one prompt, Try On turns flat-lay photos into on-model images, and BRIX AI tells you what to fix first.'],
            ['title' => 'A free plan on every app', 'text' => 'Convert, Try On and Connect each start free. Paid plans are billed through Shopify, and you can cancel anytime.'],
            ['title' => 'Apps that work together', 'text' => 'Convert grows the order, Try On builds confidence, Connect brings shoppers back. Start with one and add the others when you are ready.'],
            ['title' => 'Built for Online Store 2.0', 'text' => 'Works with modern Shopify themes through app embeds.'],
        ]),

        'home_blog_enabled' => '1',
        'home_blog_eyebrow' => 'From the blog',
        'home_blog_title'   => 'Guides on growing your Shopify store',

        'home_pricing_enabled'  => '1',
        'home_pricing_eyebrow'  => 'Pricing',
        'home_pricing_title'    => 'Start free. Upgrade when the numbers say so.',
        'home_pricing_subtitle' => 'All plans include the Cart Drawer, Announcement Bar and Brix AI. Billed through Shopify, cancel anytime.',

        'home_faq_enabled' => '1',
        'home_faq_eyebrow' => 'FAQ',
        'home_faq_title'   => 'Questions merchants ask',

        'home_cta_enabled' => '1',
        'home_cta_title'   => 'Grow every step of the sale',
        'home_cta_text'    => 'Every BRIX app has a free plan. Install the one you need, or ask BRIX AI where your store loses the most.',
    ];
}

/** Settings stored in the database, loaded once per request. */
function settings_loaded(bool $reload = false): array
{
    static $values = null;
    if ($values === null || $reload) {
        $values = [];
        try {
            foreach (db_all('SELECT `key`, `value` FROM settings') as $row) {
                $values[$row['key']] = $row['value'];
            }
        } catch (PDOException) {
            // Tables not installed yet: fall back to defaults.
            return $values;
        }
        // After a deploy, bring older databases up to date once (see app/upgrade.php).
        if (content_upgrade($values)) {
            return settings_loaded(true);
        }
    }
    return $values;
}

function setting(string $key, mixed $default = null): mixed
{
    $stored = settings_loaded();
    if (array_key_exists($key, $stored) && $stored[$key] !== null) {
        return $stored[$key];
    }
    return setting_defaults()[$key] ?? $default;
}

/** A JSON list setting (repeater rows). */
function setting_rows(string $key): array
{
    $rows = json_decode((string) setting($key, '[]'), true);
    return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
}

function setting_on(string $key): bool
{
    return (string) setting($key, '0') === '1';
}

function settings_save(array $values): void
{
    $stmt = db()->prepare('INSERT INTO settings (`key`, `value`, updated_at) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = VALUES(updated_at)');
    foreach ($values as $key => $value) {
        $stmt->execute([$key, is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value, now()]);
    }
    settings_loaded(true);
}

function anthropic_models(): array
{
    return [
        'claude-opus-5-5'   => 'Claude Opus 5.5 (default: best answers)',
        'claude-sonnet-5-5' => 'Claude Sonnet 5.5 (faster, half the cost)',
        'claude-haiku-5-5'  => 'Claude Haiku 5.5 (fastest, lowest cost)',
        'claude-fable-5-1'  => 'Claude Fable 5.1 (most capable, highest cost)',
    ];
}
