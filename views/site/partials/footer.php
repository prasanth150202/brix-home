<?php
$socials = array_filter([
    'x-social'  => setting('social_x'),
    'linkedin'  => setting('social_linkedin'),
    'instagram' => setting('social_instagram'),
    'youtube'   => setting('social_youtube'),
]);
$products = active_products();
?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <img src="<?= e(media_url((string) setting('site_logo'))) ?>" alt="<?= e(setting('site_name')) ?>" width="111" height="32" loading="lazy">
      <p><?= e(setting('footer_text')) ?></p>
    </div>
    <?php if ($products): ?>
    <div>
      <h3 class="footer-title">Products</h3>
      <ul class="footer-links">
        <?php foreach ($products as $product): ?>
          <?php if ($product['learn_more_url']): ?>
            <li><a href="<?= e(url($product['learn_more_url'])) ?>"><?= e($product['name']) ?></a></li>
          <?php else: // Each BRIX product goes to its own Shopify App Store listing. ?>
            <li><a href="<?= e(product_install_url($product)) ?>" target="_blank" rel="noopener" data-track="footer" data-product="<?= (int) $product['id'] ?>"><?= e($product['name']) ?></a></li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    <div>
      <h3 class="footer-title">Resources</h3>
      <ul class="footer-links">
        <li><a href="<?= e(url('/?chat=1')) ?>" data-open-chat>Ask BRIX AI</a></li>
        <li><a href="<?= e(url('/#results')) ?>">Case studies</a></li>
        <li><a href="<?= e(url('/blog')) ?>">Blog</a></li>
        <li><a href="<?= e(url('/#faq')) ?>">FAQ</a></li>
      </ul>
    </div>
    <div>
      <h3 class="footer-title">Company</h3>
      <ul class="footer-links">
        <?php foreach (nav_pages('footer') as $footerPage): ?>
          <li><a href="<?= e(page_url($footerPage)) ?>"><?= e($footerPage['title']) ?></a></li>
        <?php endforeach; ?>
        <?php if (setting('demo_url')): ?><li><a href="<?= e(setting('demo_url')) ?>" target="_blank" rel="noopener">Book a demo</a></li><?php endif; ?>
        <?php if (setting('contact_email')): ?><li><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li><?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="container footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= e(setting('site_name')) ?>. All rights reserved.</p>
    <?php if ($socials): ?>
    <div class="socials">
      <?php foreach ($socials as $iconName => $link): ?>
        <a href="<?= e($link) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst(str_replace('-social', '', $iconName))) ?>"><?= icon($iconName) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="footer-wordmark" aria-hidden="true"><?= e(setting('site_name')) ?></div>
</footer>
