<?php
$chatTags ??= [];
$mark = media_url((string) setting('site_mark'));
?>
<div class="chat" id="chat" role="dialog" aria-modal="true" aria-label="<?= e(setting('ai_assistant_name')) ?> chat" aria-hidden="true">
  <header class="chat-top">
    <button type="button" class="chat-btn" id="chatBack" aria-label="Back to the website"><?= icon('arrow-left') ?><span>Back to site</span></button>
    <div class="chat-brand"><span class="chat-brand-mark"><img src="<?= e($mark) ?>" alt="" width="18" height="20"></span><?= e(setting('ai_assistant_name')) ?></div>
    <button type="button" class="chat-btn" id="chatNew" aria-label="Start a new chat"><?= icon('pen') ?><span>New chat</span></button>
  </header>

  <div class="chat-scroll" id="chatScroll">
    <div class="chat-col">
      <div class="chat-empty" id="chatEmpty">
        <span class="chat-empty-mark"><img src="<?= e($mark) ?>" alt="" width="30" height="32"></span>
        <h2><?= e(setting('chat_welcome_title')) ?></h2>
        <p><?= e(setting('chat_welcome_text')) ?></p>
        <?php if ($chatTags): ?>
        <div class="chip-row">
          <?php foreach ($chatTags as $tag): ?>
            <button type="button" class="chip" data-ask="<?= e($tag['prompt'] ?: $tag['label']) ?>"><?= e($tag['label']) ?></button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="chat-thread" id="chatThread" role="log" aria-live="polite"></div>
    </div>
  </div>

  <div class="chat-dock">
    <div class="chat-col">
      <div class="dock-slot" id="dockSlot"></div>
      <p class="chat-disclaimer"><?= e(setting('chat_disclaimer')) ?></p>
    </div>
  </div>
</div>
<canvas class="dissolve-canvas" id="dissolveCanvas" aria-hidden="true"></canvas>
