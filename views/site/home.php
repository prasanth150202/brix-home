<?php
/**
 * @var array $products
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

// Looping illustrations for feature tiles, picked by the tile's icon.
$visual = static function (string $icon): string {
    $row = static fn (string $tag): string => '<div class="viz-row"><span class="viz-thumb"></span><span class="viz-lines"><i></i><i></i></span><b>' . $tag . '</b></div>';
    return match ($icon) {
        'cart' => '<div class="viz viz-cart" aria-hidden="true">' . $row('×1') . $row('×2') . '<div class="viz-btn">Checkout</div></div>',
        'sparkles' => '<div class="viz viz-upsell" aria-hidden="true">' . $row('+ Add') . $row('+ Add') . '</div>',
        'gift' => '<div class="viz viz-tiers" aria-hidden="true"><div class="viz-track"><span class="viz-fill"></span><i class="is-on" style="left:30%"></i><i class="is-on" style="left:62%"></i><i style="left:100%"></i></div><div class="viz-caption">' . icon('gift', 'icon icon-xs') . 'Free gift unlocked</div></div>',
        'ticket' => '<div class="viz viz-coupons" aria-hidden="true"><div class="viz-coupon-track">' . str_repeat('<span class="viz-coupon">SAVE10</span><span class="viz-coupon">FREESHIP</span><span class="viz-coupon">GIFT25</span>', 4) . '</div></div>',
        'banknote' => '<div class="viz viz-otp" aria-hidden="true"><span data-d="4"></span><span data-d="8"></span><span data-d="2"></span><span data-d="9"></span></div>',
        'chart' => '<div class="viz viz-bars" aria-hidden="true">' . implode('', array_map(static fn (int $h) => '<span style="--h: ' . $h . '%"></span>', [28, 42, 36, 55, 48, 66, 74, 92])) . '</div>',
        default => '',
    };
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
          <span class="product-icon">
            <?php if ($product['image']): ?><img src="<?= e(media_url($product['image'])) ?>" alt="" width="44" height="44"><?php else: ?><?= icon($product['icon'] ?: 'sparkles') ?><?php endif; ?>
          </span>
          <?php if ($product['metric_value']): ?>
            <span class="product-metric" title="<?= e($product['metric_label']) ?>"><?= icon('trending-up', 'icon icon-xs') ?><?= e($product['metric_value']) ?></span>
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

<?php if (setting_on('home_problem_enabled') || setting_on('home_how_enabled')): ?>
<div class="slab theme-light">
  <?php if (setting_on('home_problem_enabled')): ?>
  <section class="section" id="why-aov">
    <div class="container split">
      <div class="split-copy">
        <?= $head('problem', 'left') ?>
        <p class="lead reveal"><?= e(setting('home_problem_text')) ?></p>
      </div>
      <div class="compare">
        <?php foreach (setting_rows('home_problem_cards') as $i => $card): ?>
          <?php $growth = ($card['tone'] ?? '') === 'growth'; ?>
          <div class="compare-card <?= $growth ? 'is-growth theme-dark' : 'is-cost' ?> reveal" style="--r: <?= (int) $i ?>">
            <span class="compare-icon"><?= icon($growth ? 'trending-up' : 'banknote') ?></span>
            <div>
              <h3><?= e($card['title'] ?? '') ?></h3>
              <p><?= e($card['text'] ?? '') ?></p>
            </div>
            <span class="compare-bar" aria-hidden="true"><span></span></span>
          </div>
        <?php endforeach; ?>
        <figure class="aov-chart reveal" style="--r: 2">
          <figcaption class="aov-chart-head">
            <span class="aov-legend"><i class="lg-cost"></i>Cost to bring a shopper in</span>
            <span class="aov-legend"><i class="lg-brix"></i>Revenue per shopper with BRIX</span>
            <span class="aov-note">Illustration</span>
          </figcaption>
          <svg viewBox="0 0 400 150" aria-hidden="true">
            <line class="aov-grid" x1="0" y1="35" x2="400" y2="35"/><line class="aov-grid" x1="0" y1="75" x2="400" y2="75"/><line class="aov-grid" x1="0" y1="115" x2="400" y2="115"/>
            <path class="aov-cost" pathLength="100" d="M0 122 C 70 118, 130 110, 200 98 S 330 78, 400 66"/>
            <path class="aov-brix" pathLength="100" d="M0 130 C 70 126, 120 114, 180 94 S 320 36, 400 18"/>
            <circle class="aov-dot" cx="400" cy="18" r="5"/>
          </svg>
          <div class="aov-axis"><span>Before BRIX</span><span>With BRIX</span></div>
        </figure>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if (setting_on('home_how_enabled')): ?>
  <section class="section<?= setting_on('home_problem_enabled') ? ' section-top-tight' : '' ?>" id="how">
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
</div>
<?php endif; ?>

<?php if (setting_on('home_demo_enabled')): ?>
<section class="section has-dots" id="demo">
  <div class="container demo">
    <div class="demo-copy">
      <?= $head('demo', 'left') ?>
      <div class="demo-stats reveal" aria-live="polite">
        <div><span>Order value</span><strong data-demo-total><?= e($currency) ?>48</strong></div>
        <div><span>Lift</span><strong class="is-up" data-demo-lift>+0%</strong></div>
        <div><span>Nudges</span><strong data-demo-nudges>0/4</strong></div>
      </div>
      <ul class="demo-checks reveal">
        <li data-nudge="fbt">Frequently bought together</li>
        <li data-nudge="pack">Quantity pack</li>
        <li data-nudge="shipping">Free-shipping bar</li>
        <li data-nudge="gift">Free gift tier</li>
      </ul>
      <button type="button" class="btn btn-ghost btn-sm reveal" data-demo-reset><?= icon('refresh', 'icon icon-sm') ?>Reset demo</button>
    </div>

    <div class="demo-cart reveal" data-demo data-currency="<?= e($currency) ?>" data-shipping="75" data-gift="120" aria-label="Interactive cart demo">
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
  </div>
</section>
<?php endif; ?>

<?php if (setting_on('home_features_enabled') && ($features = setting_rows('home_features'))): ?>
<section class="section has-dots" id="features">
  <div class="container">
    <?= $head('features') ?>
    <div class="bento">
      <?php foreach ($features as $i => $feature): ?>
        <?php $iconName = $feature['icon'] ?? 'sparkles'; $size = $feature['size'] ?? 'normal'; ?>
        <div class="bento-tile<?= $size === 'wide' ? ' is-wide' : ($size === 'full' ? ' is-full' : '') ?> reveal" style="--r: <?= (int) $i % 3 ?>" data-dissolve>
          <div class="tile-head">
            <span class="tile-icon"><?= icon($iconName) ?></span>
            <?= $visual($iconName) ?>
          </div>
          <h3><?= e($feature['title'] ?? '') ?></h3>
          <p><?= e($feature['text'] ?? '') ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($caseStudies): ?>
<div class="slab theme-light">
  <section class="section" id="results">
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
      <div class="cta-actions">
        <a class="btn btn-light" href="<?= e($installUrl) ?>" target="_blank" rel="noopener" data-track="cta" data-magnetic><?= e(setting('home_cta_button')) ?><?= icon('arrow-up-right', 'icon icon-sm icon-arrow') ?></a>
        <button type="button" class="btn btn-outline-light" data-open-chat data-magnetic><?= icon('sparkles', 'icon icon-sm') ?>Ask BRIX AI</button>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
