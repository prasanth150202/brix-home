<?php
/** @var string $content */
$meta ??= page_meta();
$isHome ??= false;
$schema = !empty($meta['schema']) ? array_filter($meta['schema'], static fn ($v) => $v !== null) : null;
$siteName = (string) setting('site_name');
?><!doctype html>
<html lang="en" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($meta['title']) ?></title>
<meta name="description" content="<?= e($meta['description']) ?>">
<link rel="canonical" href="<?= e($meta['canonical']) ?>">
<?php if ($meta['noindex']): ?><meta name="robots" content="noindex, follow">
<?php endif; ?>
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:type" content="<?= e($meta['type']) ?>">
<meta property="og:title" content="<?= e($meta['title']) ?>">
<meta property="og:description" content="<?= e($meta['description']) ?>">
<meta property="og:url" content="<?= e($meta['canonical']) ?>">
<meta property="og:image" content="<?= e($meta['image']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#202124">
<link rel="icon" href="<?= e(media_url((string) setting('site_favicon'))) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
<?php if ($schema): ?><script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif; ?>
<?= setting('head_code') ?>
</head>
<body class="<?= $isHome ? 'is-home' : 'is-inner' ?>">
<a class="skip-link" href="#main">Skip to content</a>
<div class="bg-fx" aria-hidden="true"><span class="bg-glow"></span><span class="bg-grid"></span><span class="bg-noise"></span></div>

<div class="site" id="site">
<?php partial('site/partials/header', ['isHome' => $isHome]); ?>
<main id="main">
<?= $content ?>
</main>
<?php partial('site/partials/footer'); ?>
</div>

<script id="brixConfig" type="application/json"><?= json_encode([
    'chatUrl'   => url('/api/chat'),
    'trackUrl'  => url('/api/track'),
    'csrf'      => chat_token(),
    'assistant' => (string) setting('ai_assistant_name'),
    'mark'      => media_url((string) setting('site_mark')),
    // Example questions the hero chat box types out as its placeholder.
    'examples'  => array_values(array_map(static fn ($t) => $t['prompt'] ?: $t['label'], $heroTags ?? [])),
    'icons'     => [
        'sparkles' => icon('sparkles', 'icon icon-xs'),
        'check'    => icon('check', 'icon'),
        'arrow'    => icon('arrow-up-right', 'icon icon-sm'),
        'file'     => icon('file', 'icon icon-xs'),
        'copy'     => icon('copy', 'icon'),
        'retry'    => icon('refresh', 'icon'),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php if ($isHome): ?>
<?php partial('site/partials/chat', ['chatTags' => $chatTags ?? []]); ?>
<script src="<?= e(asset('assets/vendor/marked.umd.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/vendor/purify.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/dissolve.js')) ?>" defer></script>
<?php endif; ?>
<script src="<?= e(asset('assets/js/site.js')) ?>" defer></script>
<?= setting('body_code') ?>
</body>
</html>
