<?php /** @var array $errors */ ?>
<form method="post" class="form-layout">
  <?= csrf_field() ?>
  <div class="form-main">
    <section class="card">
      <div class="card-head"><h2>Brand</h2></div>
      <div class="field-row">
        <?= form_field('text', 'site_name', 'Site name', setting('site_name'), ['required' => true, 'error' => $errors['site_name'] ?? null]) ?>
        <?= form_field('text', 'site_tagline', 'Tagline', setting('site_tagline')) ?>
      </div>
      <div class="field-row">
        <?= form_field('image', 'site_logo', 'Logo (light, for the dark site)', setting('site_logo')) ?>
        <?= form_field('image', 'site_mark', 'Logo mark (square)', setting('site_mark')) ?>
      </div>
      <?= form_field('image', 'site_favicon', 'Favicon', setting('site_favicon')) ?>
      <?= form_field('textarea', 'footer_text', 'Footer text', setting('footer_text'), ['rows' => 2]) ?>
    </section>

    <section class="card">
      <div class="card-head"><h2>Links</h2></div>
      <?= form_field('url', 'install_url', 'Shopify App Store install link', setting('install_url'), ['error' => $errors['install_url'] ?? null, 'help' => 'Used by every "Install" button that has no link of its own.']) ?>
      <div class="field-row">
        <?= form_field('url', 'demo_url', 'Book-a-demo link', setting('demo_url'), ['error' => $errors['demo_url'] ?? null, 'help' => 'Optional. Shown in the footer.']) ?>
        <?= form_field('email', 'contact_email', 'Contact email', setting('contact_email'), ['error' => $errors['contact_email'] ?? null]) ?>
      </div>
      <div class="field-row">
        <?= form_field('url', 'social_x', 'X (Twitter)', setting('social_x'), ['error' => $errors['social_x'] ?? null]) ?>
        <?= form_field('url', 'social_linkedin', 'LinkedIn', setting('social_linkedin'), ['error' => $errors['social_linkedin'] ?? null]) ?>
      </div>
      <div class="field-row">
        <?= form_field('url', 'social_instagram', 'Instagram', setting('social_instagram'), ['error' => $errors['social_instagram'] ?? null]) ?>
        <?= form_field('url', 'social_youtube', 'YouTube', setting('social_youtube'), ['error' => $errors['social_youtube'] ?? null]) ?>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><h2>Code</h2></div>
      <?= form_field('code', 'head_code', 'Code before </head>', setting('head_code'), ['rows' => 5, 'help' => 'Analytics, verification tags, pixels. Added to every public page.']) ?>
      <?= form_field('code', 'body_code', 'Code before </body>', setting('body_code'), ['rows' => 4]) ?>
    </section>
  </div>

  <aside class="form-side">
    <section class="card">
      <div class="card-head"><h2>Search defaults</h2></div>
      <?= form_field('text', 'seo_title', 'Homepage title', setting('seo_title'), ['count' => 60]) ?>
      <?= form_field('textarea', 'seo_description', 'Homepage description', setting('seo_description'), ['rows' => 4, 'count' => 160]) ?>
      <?= form_field('image', 'og_image', 'Default share image', setting('og_image'), ['help' => '1200×630 works best.']) ?>
    </section>
    <section class="card">
      <div class="card-head"><h2>Pricing</h2></div>
      <?= form_field('text', 'currency_symbol', 'Currency symbol', setting('currency_symbol'), ['placeholder' => '$']) ?>
    </section>
    <button class="btn btn-primary btn-block" type="submit">Save settings</button>
  </aside>
</form>
