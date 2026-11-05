<?php
/**
 * @var array $products
 * @var array $apps
 * @var array $heroTags
 * @var array $caseStudies
 * @var array $testimonials
 * @var array $plans
 * @var array $faqs
 * @var array $posts
 */
$titleWords = preg_split('/\s+/', trim((string) setting('hero_title'))) ?: ['BRIX', 'AI'];
$titleLast = count($titleWords) > 1 ? array_pop($titleWords) : null;
$currency = (string) setting('currency_symbol', '$');
$installUrl = (string) setting('install_url');
$mark = media_url((string) setting('site_mark'));
$stageBySlug = array_column(array_map(static fn (array $a) => ['slug' => $a['item']['slug'], 'stage' => $a['stage'] ?? ''], $apps), 'stage', 'slug');
// The closing call to action offers each app; without app rows it falls back to the product list.
$ctaApps = $apps ?: array_map(static fn (array $p) => ['item' => $p, 'stage' => ''], $products);

$head = static function (string $prefix, string $align = 'center'): string {
    $eyebrow = (string) setting("home_{$prefix}_eyebrow", '');
    $title = (string) setting("home_{$prefix}_title", '');
    $subtitle = (string) setting("home_{$prefix}_subtitle", '');
    $html = '<div class="section-head section-head-' . $align . '" data-reveal data-dissolve>';
    if ($eyebrow !== '') {
        $html .= '<p class="eyebrow">' . e($eyebrow) . '</p>';
    }
    $html .= '<h2 class="section-title" data-split>' . e($title) . '</h2>';
    if ($subtitle !== '') {
        $html .= '<p class="section-sub">' . e($subtitle) . '</p>';
    }
    return $html . '</div>';
};

// A product's logo, or its icon when no logo is uploaded.
$logo = static function (array $product, string $class = 'product-icon'): string {
    $inner = $product['image']
        ? '<img src="' . e(media_url($product['image'])) . '" alt="" width="44" height="44" loading="lazy">'
        : icon($product['icon'] ?: 'sparkles');
    return '<span class="' . $class . ($product['image'] ? ' is-image' : '') . '">' . $inner . '</span>';
};

// A small upward line that draws itself in. Decoration, not data.
$spark = static function (int $variant, string $class): string {
    $paths = [
        ['M1 28 C 14 26, 22 22, 34 23 S 52 16, 62 17 S 80 10, 92 11 S 110 5, 119 3', 3],
        ['M1 27 L 18 25 L 30 19 L 44 21 L 60 14 L 76 15 L 92 8 L 119 4', 4],
        ['M1 29 C 20 28, 30 26, 44 20 S 70 18, 82 12 S 104 8, 119 2', 2],
        ['M1 26 L 16 27 L 32 21 L 48 22 L 62 15 L 80 12 L 96 9 L 119 5', 5],
    ];
    [$d, $end] = $paths[$variant % count($paths)];
    return '<svg class="' . $class . '" viewBox="0 0 120 32" aria-hidden="true"><path pathLength="100" d="' . $d . '"/><circle cx="119" cy="' . $end . '" r="3"/></svg>';
};

// Illustrations for the first three "How it works" steps.
$stepVisual = static function (int $index) use ($mark, $spark): string {
    return match ($index) {
        0 => '<div class="sv sv-ask" aria-hidden="true"><div class="sv-input"><span class="sv-typed">How do I raise my AOV?</span><span class="sv-send">' . icon('arrow-up', 'icon') . '</span></div></div>',
        1 => '<div class="sv sv-proof" aria-hidden="true"><div class="sv-case"><strong>+41%</strong><span>AOV · skincare case study</span></div>' . $spark(2, 'sv-spark') . '</div>',
        2 => '<div class="sv sv-install" aria-hidden="true"><div class="sv-app"><span class="sv-app-icon"><img src="' . e($mark) . '" alt=""></span><span><b>BRIX</b><small>Shopify app</small></span><span class="sv-toggle"><i></i></span></div><p class="sv-live">' . icon('check', 'icon icon-xs') . 'Live on your store</p></div>',
        default => '',
    };
};
?>

<section class="hero" id="top">
  <span class="hero-spot" aria-hidden="true"></span>
  <div class="container hero-center">
    <h1 class="hero-title" data-dissolve><?php foreach ($titleWords as $word): ?><span class="w"><span class="wi"><?= e($word) ?></span></span> <?php endforeach; ?><?php if ($titleLast !== null): ?><span class="w"><span class="wi grad" data-dissolve-gradient="#FFFFFF,#8C8C8C,#FFFFFF"><?= e($titleLast) ?></span></span><?php endif; ?></h1>
    <p class="hero-sub" data-dissolve><?= e(setting('hero_subtitle')) ?></p>

    <div class="composer-slot" id="composerSlot">
      <form class="composer" id="composer" autocomplete="off">
        <label class="sr-only" for="composerInput">Ask <?= e(setting('ai_assistant_name')) ?></label>
        <textarea id="composerInput" name="message" rows="1" maxlength="<?= (int) setting('ai_max_question_chars', 1000) ?>" placeholder="<?= e(setting('chat_placeholder')) ?>"></textarea>
        <div class="composer-bar">
          <span class="composer-hint"><?= icon('sparkles', 'icon icon-xs') ?><?= e(setting('chat_hint')) ?></span>
          <button class="composer-send" id="composerSend" type="submit" aria-label="Send" disabled>
            <span class="send-icon"><?= icon('arrow-up') ?></span><span class="stop-icon"><?= icon('stop') ?></span>
          </button>
        </div>
      </form>
    </div>

    <?php if ($heroTags): ?>
    <div class="chip-row hero-tags" data-dissolve>
      <?php foreach ($heroTags as $i => $tag): ?>
        <button type="button" class="chip" style="--i: <?= (int) $i ?>" data-ask="<?= e($tag['prompt'] ?: $tag['label']) ?>"><?= e($tag['label']) ?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($products): ?>
  <div class="container hero-products" id="products">
    <p class="hero-products-title" data-dissolve><?= e(setting('products_title')) ?></p>
    <div class="product-grid">
      <?php foreach ($products as $i => $product): ?>
      <article class="product-card" data-dissolve data-tilt style="--i: <?= (int) $i ?>">
        <div class="product-top">
          <?= $logo($product) ?>
          <?php if ($product['metric_value']): ?>
            <span class="product-metric" title="<?= e($product['metric_label']) ?>"><?= icon('trending-up', 'icon icon-xs') ?><?= e($product['metric_value']) ?></span>
          <?php elseif (!empty($stageBySlug[$product['slug']])): ?>
            <a class="product-stage" href="#app-<?= e($product['slug']) ?>"><?= e($stageBySlug[$product['slug']]) ?></a>
          <?php endif; ?>
        </div>
        <h3><?= e($product['name']) ?></h3>
        <p><?= e($product['tagline']) ?></p>
        <div class="product-actions">
          <button type="button" class="link-btn link-ai" data-ask="<?= e('What does ' . $product['name'] . ' do, and how would it help my store?') ?>"><?= icon('sparkles', 'icon icon-xs') ?>Ask BRIX AI</button>
          <a class="link-btn" href="<?= e(product_install_url($product)) ?>" target="_blank" rel="noopener" data-track="hero" data-product="<?= (int) $product['id'] ?>">Install<?= icon('arrow-up-right', 'icon icon-xs') ?></a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<?php $brands = setting_on('home_brands_enabled') ? array_filter(setting_rows('home_brands'), static fn (array $b) => !empty($b['logo'])) : []; ?>
<?php if ($brands): ?>
<section class="marquee marquee-logos" aria-label="Brands growing with BRIX">
  <div class="marquee-track">
    <?php for ($copy = 0; $copy < 2; $copy++): ?>
      <div class="marquee-group"<?= $copy ? ' aria-hidden="true"' : '' ?>>
        <?php // Logos run twice per group so a short list still fills wide screens. ?>
        <?php foreach (array_merge($brands, $brands) as $brand): ?>
          <img class="marquee-logo" src="<?= e(media_url($brand['logo'])) ?>" alt="<?= $copy ? '' : e($brand['name'] ?? '') ?>" loading="lazy" decoding="async">
        <?php endforeach; ?>
      </div>
    <?php endfor; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($apps): ?>
<div class="slab theme-light">
  <section class="section" id="apps">
    <div class="container">
      <?= $head('apps') ?>
      <ol class="journey" style="--count: <?= count($apps) ?>" data-reveal>
        <?php foreach ($apps as $i => $app): ?>
          <li class="journey-step reveal" style="--r: <?= (int) $i * 2 ?>">
            <span class="journey-dot" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <div class="journey-card">
              <?php if (!empty($app['stage'])): ?><p class="journey-stage"><?= e($app['stage']) ?></p><?php endif; ?>
              <div class="journey-app">
                <?= $logo($app['item']) ?>
                <h3><?= e($app['item']['name']) ?></h3>
              </div>
              <?php if (!empty($app['problem'])): ?><p class="journey-problem"><?= e($app['problem']) ?></p><?php endif; ?>
              <a class="link-btn journey-link" href="#app-<?= e($app['item']['slug']) ?>">See how it works<?= icon('arrow-right', 'icon icon-xs') ?></a>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>
</div>

<?php foreach ($apps as $i => $app): ?>
<?php $item = $app['item']; $visualType = $app['visual'] ?? 'none'; ?>
<section class="section app-show has-dots" id="app-<?= e($item['slug']) ?>">
  <div class="container app-grid<?= $i % 2 ? ' is-flipped' : '' ?>">
    <div class="app-copy">
      <p class="eyebrow reveal"><?= e(implode(' · ', array_filter([str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), $app['stage'] ?? '']))) ?></p>
      <div class="app-brand reveal">
        <?= $logo($item) ?>
        <span><strong><?= e($item['name']) ?></strong><small><?= e($item['tagline']) ?></small></span>
      </div>
      <h2 class="app-title" data-split><?= e($app['title'] ?: $item['name']) ?></h2>
      <?php if (!empty($app['text'])): ?><p class="lead reveal"><?= e($app['text']) ?></p><?php endif; ?>
      <?php if ($app['points_list']): ?>
      <ul class="app-points reveal">
        <?php foreach ($app['points_list'] as $point): ?><li><?= icon('check', 'icon icon-xs') ?><?= e($point) ?></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <div class="app-actions reveal">
        <a class="btn btn-primary" href="<?= e(product_install_url($item)) ?>" target="_blank" rel="noopener" data-track="apps" data-product="<?= (int) $item['id'] ?>" data-magnetic>Install <?= e($item['name']) ?><?= icon('arrow-up-right', 'icon icon-sm icon-arrow') ?></a>
        <button type="button" class="btn btn-ghost" data-ask="<?= e('What does ' . $item['name'] . ' do, and how would it help my store?') ?>"><?= icon('sparkles', 'icon icon-sm') ?>Ask BRIX AI</button>
      </div>
    </div>

    <div class="app-visual reveal">
      <?php if ($visualType === 'tryon'): ?>
        <?php // The shopper's own photo, then the same photo wearing the jacket once the try-on finishes. ?>
        <div class="viz-tryon" aria-hidden="true">
          <div class="to-card">
            <div class="to-stage">
              <img class="to-photo to-before" src="<?= e(url('assets/img/tryon/before.webp')) ?>" alt="" width="760" height="1132" loading="lazy" decoding="async">
              <img class="to-photo to-after" src="<?= e(url('assets/img/tryon/after.webp')) ?>" alt="" width="760" height="1132" loading="lazy" decoding="async">
              <span class="to-scan"></span>
              <span class="to-label to-label-before"><?= icon('upload', 'icon icon-xs') ?>Your photo</span>
              <span class="to-label to-label-after"><?= icon('sparkles', 'icon icon-xs') ?>Try-on ready</span>
            </div>
            <div class="to-product">
              <img src="<?= e(url('assets/img/tryon/jacket.webp')) ?>" alt="" width="56" height="56" loading="lazy" decoding="async">
              <div><b>Leather Biker Jacket</b><span>Dark brown · <?= e($currency) ?>189</span></div>
            </div>
            <div class="to-buttons"><span class="to-try"><?= icon('eye', 'icon icon-xs') ?>Try it on</span><span class="to-add">Add to cart</span></div>
          </div>
          <span class="to-chip to-chip-garment"><img src="<?= e(url('assets/img/tryon/jacket.webp')) ?>" alt="" width="28" height="28" loading="lazy" decoding="async">Trying on: Leather Biker Jacket</span>
          <span class="to-chip to-chip-lead"><?= icon('mail', 'icon icon-xs') ?>Lead captured</span>
        </div>
      <?php elseif ($visualType === 'convert'): ?>
        <div class="demo-stats demo-stats-sm" aria-live="polite">
          <div><span>Order value</span><strong data-demo-total><?= e($currency) ?>48</strong></div>
          <div><span>Lift</span><strong class="is-up" data-demo-lift>+0%</strong></div>
          <button type="button" class="btn btn-ghost btn-sm" data-demo-reset><?= icon('refresh', 'icon icon-sm') ?>Reset</button>
        </div>
        <div class="demo-cart" data-demo data-currency="<?= e($currency) ?>" data-shipping="75" data-gift="120" aria-label="Interactive cart demo">
          <div class="dc-head"><strong>Your cart</strong><span data-demo-count>1 item</span></div>
          <div class="dc-progress">
            <p class="dc-msg" data-demo-msg>Add <?= e($currency) ?>27 more for free shipping</p>
            <div class="dc-track">
              <span class="dc-fill" data-demo-fill></span>
              <i class="dc-mark" style="left: 62.5%" data-mark="shipping" title="Free shipping"><?= icon('truck', 'icon') ?></i>
              <i class="dc-mark" style="left: 100%" data-mark="gift" title="Free gift"><?= icon('gift', 'icon') ?></i>
            </div>
          </div>
          <ul class="dc-items" data-demo-items>
            <li class="dc-item"><span class="dc-thumb" style="--c:#E7E5E4"></span><div><b>Hydrating Serum</b><small>30 ml</small></div><em><?= e($currency) ?>48</em></li>
          </ul>
          <div class="dc-suggest">
            <p>Frequently bought together</p>
            <div class="dc-cards">
              <button type="button" class="dc-card" data-add="cleanser" data-name="Gentle Cleanser" data-note="150 ml" data-price="22" data-shade="#E7E5E4"><span class="dc-thumb" style="--c:#E7E5E4"></span><b>Gentle Cleanser</b><span><?= e($currency) ?>22</span><span class="dc-add">+ Add</span></button>
              <button type="button" class="dc-card" data-add="cream" data-name="Night Cream" data-note="50 ml" data-price="34" data-shade="#D6D3D1"><span class="dc-thumb" style="--c:#D6D3D1"></span><b>Night Cream</b><span><?= e($currency) ?>34</span><span class="dc-add">+ Add</span></button>
              <button type="button" class="dc-card" data-add="spf" data-name="Daily SPF 50" data-note="40 ml" data-price="26" data-shade="#F5F5F4"><span class="dc-thumb" style="--c:#F5F5F4"></span><b>Daily SPF 50</b><span><?= e($currency) ?>26</span><span class="dc-add">+ Add</span></button>
            </div>
          </div>
          <div class="dc-foot">
            <div><span>Subtotal</span><strong data-demo-subtotal><?= e($currency) ?>48</strong></div>
            <span class="dc-checkout">Checkout</span>
          </div>
        </div>
      <?php elseif ($visualType === 'connect'): ?>
        <div class="viz-connect" aria-hidden="true">
          <div class="cn-phone">
            <div class="cn-screen">
            <div class="cn-status">
              <span class="cn-carrier">BRIX</span>
              <span class="cn-island"></span>
              <span class="cn-sys">
                <svg viewBox="0 0 18 12" width="17" height="11"><rect x="0" y="8" width="3" height="4" rx="1"/><rect x="5" y="5.5" width="3" height="6.5" rx="1"/><rect x="10" y="3" width="3" height="9" rx="1"/><rect x="15" y="0" width="3" height="12" rx="1"/></svg>
                <svg viewBox="0 0 16 12" width="15" height="11"><path d="M8 11.5 5.6 9a3.4 3.4 0 0 1 4.8 0z"/><path d="M3.4 6.8a6.5 6.5 0 0 1 9.2 0l-1.4 1.4a4.5 4.5 0 0 0-6.4 0z"/><path d="M1.2 4.6a9.6 9.6 0 0 1 13.6 0l-1.4 1.4a7.6 7.6 0 0 0-10.8 0z"/></svg>
                <span class="cn-battery"><i></i></span>
              </span>
            </div>
            <div class="cn-clock"><span>Tuesday 9 June</span><strong>9:41</strong></div>
            <div class="cn-stack">
              <div class="cn-note" style="--n: 0"><span class="cn-icon"><?= icon('cart', 'icon icon-sm') ?></span><div><small>Your store</small><b>You left something behind</b><p>Your Linen Overshirt is still in your cart.</p></div></div>
              <div class="cn-note" style="--n: 1"><span class="cn-icon"><?= icon('zap', 'icon icon-sm') ?></span><div><small>Your store</small><b>Flash sale ends tonight</b><p>20% off everything until midnight.</p></div></div>
              <div class="cn-note" style="--n: 2"><span class="cn-icon"><?= icon('sparkles', 'icon icon-sm') ?></span><div><small>Your store</small><b>New arrivals are in</b><p>The summer edit just dropped. Take a look.</p></div></div>
            </div>
            <div class="cn-dock">
              <span class="cn-round"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6c0 2-2 2-2 4v10a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2V10c0-2-2-2-2-4V2h12z"/><path d="M6 6h12"/><path d="M12 12v2"/></svg></span>
              <span class="cn-round"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z"/><circle cx="12" cy="13" r="3"/></svg></span>
            </div>
            <span class="cn-home"></span>
            </div>
          </div>
          <span class="cn-optin"><b>Get updates from this store?</b><i>Allow</i></span>
          <span class="cn-result"><?= icon('check', 'icon icon-xs') ?>Shopper came back · Order placed</span>
        </div>
      <?php else: ?>
        <div class="viz-logo" aria-hidden="true"><?= $logo($item) ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endforeach; ?>
<?php endif; ?>

<?php if (setting_on('home_stats_enabled') && ($stats = setting_rows('home_stats'))): ?>
<section class="stats-strip" aria-label="Results from BRIX case studies">
  <div class="container stats-grid">
    <?php foreach ($stats as $i => $stat): ?>
      <?php
      $value = (string) ($stat['value'] ?? '');
      $count = preg_match('/^(\D*)(\d+(?:\.\d+)?)(.*)$/u', str_replace(',', '', $value), $m) ? $m : null;
      ?>
      <div class="stat reveal" style="--r: <?= (int) $i ?>" data-dissolve>
        <strong class="stat-value"<?php if ($count): ?> data-count="<?= e($count[2]) ?>" data-prefix="<?= e($count[1]) ?>" data-suffix="<?= e($count[3]) ?>"<?php endif; ?>><?= e($value) ?></strong>
        <?= $spark($i, 'stat-spark') ?>
        <span class="stat-label"><?= e($stat['label'] ?? '') ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (setting_on('home_how_enabled') || $caseStudies): ?>
<div class="slab theme-light">
  <?php if (setting_on('home_how_enabled')): ?>
  <section class="section" id="how">
    <div class="container">
      <?= $head('how') ?>
      <ol class="steps">
        <?php foreach (setting_rows('home_how_steps') as $i => $step): ?>
          <li class="step reveal" style="--r: <?= (int) $i ?>">
            <?= $stepVisual((int) $i) ?>
            <span class="step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <h3><?= e($step['title'] ?? '') ?></h3>
            <p><?= e($step['text'] ?? '') ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
      <div class="section-cta reveal">
        <button type="button" class="btn btn-primary" data-open-chat data-magnetic><?= icon('sparkles', 'icon icon-sm') ?>Try BRIX AI now</button>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($caseStudies): ?>
  <section class="section<?= setting_on('home_how_enabled') ? ' section-top-tight' : '' ?>" id="results">
    <div class="container">
      <?= $head('results') ?>
      <div class="case-grid">
        <?php foreach ($caseStudies as $i => $doc): ?>
          <article class="case-card reveal" style="--r: <?= (int) $i % 3 ?>">
            <?php if ($doc['result_value']): ?>
            <div class="case-metric">
              <span class="case-value"><?= e($doc['result_value']) ?></span>
              <?= $spark($i + 1, 'case-spark') ?>
              <span class="case-label"><?= icon('trending-up', 'icon icon-xs') ?><?= e($doc['result_label']) ?></span>
            </div>
            <?php endif; ?>
            <h3><?= e($doc['title']) ?></h3>
            <?php if ($doc['brand'] || $doc['industry']): ?>
              <p class="case-meta"><?= e(implode(' · ', array_filter([$doc['brand'], $doc['industry']]))) ?></p>
            <?php endif; ?>
            <?php if ($doc['summary']): ?><p class="case-summary"><?= e(str_limit((string) $doc['summary'], 170)) ?></p><?php endif; ?>
            <div class="case-actions">
              <button type="button" class="btn btn-primary btn-sm" data-ask="<?= e('Walk me through the case study "' . $doc['title'] . '". What did they change, and how can I do the same in my store?') ?>"><?= icon('sparkles', 'icon icon-sm') ?>Ask BRIX AI how</button>
              <?php if ($doc['public_url']): ?>
                <a class="link-btn" href="<?= e(url($doc['public_url'])) ?>">Read it<?= icon('arrow-right', 'icon icon-xs') ?></a>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($testimonials): ?>
<?php
$repeat = max(1, (int) ceil(6 / count($testimonials)));
$duration = max(36, count($testimonials) * $repeat * 9);
?>
<section class="section" id="testimonials">
  <div class="container">
    <?= $head('testimonials') ?>
  </div>
  <div class="quote-marquee" style="--duration: <?= $duration ?>s" aria-label="Testimonials">
    <div class="quote-track">
      <?php for ($copy = 0; $copy < 2; $copy++): ?>
        <div class="quote-group"<?= $copy ? ' aria-hidden="true"' : '' ?>>
          <?php for ($r = 0; $r < $repeat; $r++): foreach ($testimonials as $quote): ?>
            <figure class="quote-card">
              <div class="quote-top">
                <?php if ($quote['metric_value']): ?><span class="quote-metric"><?= e($quote['metric_value']) ?> <small><?= e($quote['metric_label']) ?></small></span><?php endif; ?>
                <span class="stars" aria-label="<?= (int) $quote['rating'] ?> out of 5 stars"><?= str_repeat(icon('star', 'icon icon-xs star'), max(1, min(5, (int) $quote['rating']))) ?></span>
              </div>
              <blockquote>“<?= e($quote['quote']) ?>”</blockquote>
              <figcaption>
                <?php if ($quote['avatar']): ?>
                  <img src="<?= e(media_url($quote['avatar'])) ?>" alt="" width="40" height="40" loading="lazy">
                <?php else: ?>
                  <span class="avatar"><?= e(mb_strtoupper(implode('', array_map(static fn ($w) => mb_substr($w, 0, 1), array_slice(explode(' ', $quote['name']), 0, 2))))) ?></span>
                <?php endif; ?>
                <span><strong><?= e($quote['name']) ?></strong><?php if ($quote['role']): ?><small><?= e($quote['role']) ?></small><?php endif; ?></span>
              </figcaption>
            </figure>
          <?php endforeach; endfor; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (setting_on('home_why_enabled') && ($reasons = setting_rows('home_why'))): ?>
<section class="section" id="why-brix">
  <div class="container why">
    <?= $head('why', 'left') ?>
    <ol class="why-list">
      <?php foreach ($reasons as $i => $reason): ?>
        <li class="why-row reveal" data-dissolve>
          <span class="why-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div>
            <h3><?= e($reason['title'] ?? '') ?></h3>
            <p><?= e($reason['text'] ?? '') ?></p>
          </div>
          <span class="why-arrow"><?= icon('arrow-up-right') ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
<?php endif; ?>

<?php if ($posts || $plans || $faqs): ?>
<div class="slab theme-light">
  <?php if ($posts): ?>
  <section class="section" id="blog">
    <div class="container">
      <div class="section-head-row">
        <?= $head('blog', 'left') ?>
        <a class="btn btn-ghost btn-sm reveal" href="<?= e(url('/blog')) ?>">All articles<?= icon('arrow-right', 'icon icon-sm') ?></a>
      </div>
      <div class="post-grid">
        <?php foreach ($posts as $i => $post): ?>
          <?php partial('site/partials/post-card', ['post' => $post, 'index' => $i]); ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($plans): ?>
  <?php
  $savings = 0;
  foreach ($plans as $plan) {
      if ($plan['price_yearly'] !== null && (float) $plan['price_monthly'] > 0) {
          $savings = max($savings, (int) round((1 - (float) $plan['price_yearly'] / ((float) $plan['price_monthly'] * 12)) * 100));
      }
  }
  ?>
  <section class="section<?= $posts ? ' section-top-tight' : '' ?>" id="pricing">
    <div class="container">
      <?= $head('pricing') ?>
      <?php if ($savings > 0): ?>
      <div class="billing-toggle reveal" role="group" aria-label="Billing period">
        <span class="billing-indicator" aria-hidden="true"></span>
        <button type="button" class="is-active" data-billing="monthly" aria-pressed="true">Monthly</button>
        <button type="button" data-billing="yearly" aria-pressed="false">Yearly <span class="save">Save <?= $savings ?>%</span></button>
      </div>
      <?php endif; ?>
      <div class="plan-grid">
        <?php foreach ($plans as $i => $plan): ?>
          <?php $monthly = (float) $plan['price_monthly']; $yearly = $plan['price_yearly'] !== null ? (float) $plan['price_yearly'] : null; ?>
          <article class="plan <?= $plan['is_highlighted'] ? 'is-highlighted theme-dark' : '' ?> reveal" style="--r: <?= (int) $i ?>">
            <?php if ($plan['badge']): ?><span class="plan-badge"><?= e($plan['badge']) ?></span><?php endif; ?>
            <h3><?= e($plan['name']) ?></h3>
            <?php if ($plan['tagline']): ?><p class="plan-tagline"><?= e($plan['tagline']) ?></p><?php endif; ?>
            <?php if ($monthly <= 0): ?>
              <div class="plan-price"><span class="amount">Free</span><span class="per">forever</span></div>
            <?php else: ?>
              <div class="plan-price" data-monthly="<?= e(money($monthly)) ?>" data-yearly="<?= $yearly !== null ? e(money($yearly)) : '' ?>">
                <span class="currency"><?= e($currency) ?></span><span class="amount"><?= e(money($monthly)) ?></span><span class="per">/month</span>
              </div>
            <?php endif; ?>
            <a class="btn <?= $plan['is_highlighted'] ? 'btn-primary' : 'btn-ghost' ?> btn-block" href="<?= e($plan['cta_url'] ?: $installUrl) ?>" target="_blank" rel="noopener" data-track="pricing"><?= e($plan['cta_label'] ?: 'Get started') ?></a>
            <?php if ($features = lines($plan['features'])): ?>
            <ul class="plan-features">
              <?php foreach ($features as $feature): ?><li><?= icon('check', 'icon icon-sm') ?><?= e($feature) ?></li><?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($faqs): ?>
  <section class="section<?= $posts || $plans ? ' section-top-tight' : '' ?>" id="faq">
    <div class="container narrow">
      <?= $head('faq') ?>
      <div class="faq-list">
        <?php foreach ($faqs as $i => $faq): ?>
          <details class="faq reveal" style="--r: <?= (int) min($i, 5) ?>">
            <summary><span><?= e($faq['question']) ?></span><?= icon('plus', 'icon faq-icon') ?></summary>
            <div class="faq-answer"><?= nl2br(e($faq['answer'])) ?></div>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (setting_on('home_cta_enabled')): ?>
<section class="section">
  <div class="container">
    <div class="cta-band reveal" data-dissolve>
      <h2 data-split><?= e(setting('home_cta_title')) ?></h2>
      <p><?= e(setting('home_cta_text')) ?></p>
      <?php if ($ctaApps): ?>
      <div class="cta-apps">
        <?php foreach ($ctaApps as $app): ?>
          <a class="cta-app" href="<?= e(product_install_url($app['item'])) ?>" target="_blank" rel="noopener" data-track="cta" data-product="<?= (int) $app['item']['id'] ?>">
            <?= $logo($app['item']) ?>
            <span><strong><?= e($app['item']['name']) ?></strong><?php if (!empty($app['stage'])): ?><small><?= e($app['stage']) ?></small><?php endif; ?></span>
            <?= icon('arrow-up-right', 'icon icon-sm icon-arrow') ?>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="cta-actions">
        <button type="button" class="btn btn-outline-light" data-open-chat data-magnetic><?= icon('sparkles', 'icon icon-sm') ?>Not sure? Ask BRIX AI which app fits</button>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
