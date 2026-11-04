<?php
/** @var array $errors */
$provider = (string) field_value('ai_provider', setting('ai_provider'));
$anthropicKey = masked_key('anthropic_api_key');
$openrouterKey = masked_key('openrouter_api_key');
?>
<form method="post" class="form-layout">
  <?= csrf_field() ?>
  <div class="form-main">
    <section class="card">
      <div class="card-head"><h2>Provider</h2><button type="button" class="btn btn-sm" data-ai-test data-url="<?= e(url('/admin/ai/test')) ?>"><?= icon('zap', 'icon icon-sm') ?>Test saved connection</button></div>
      <div class="ai-test-result" data-ai-test-result hidden></div>
      <div class="provider-pick">
        <label class="provider-option">
          <input type="radio" name="ai_provider" value="anthropic" <?= $provider !== 'openrouter' ? 'checked' : '' ?> data-provider-switch>
          <span><strong>Claude (Anthropic)</strong><small>Best at answering only from your documents and declining off-topic questions. Billed per use by Anthropic.</small></span>
        </label>
        <label class="provider-option">
          <input type="radio" name="ai_provider" value="openrouter" <?= $provider === 'openrouter' ? 'checked' : '' ?> data-provider-switch>
          <span><strong>OpenRouter</strong><small>Hundreds of models through one key, including free ones. Free models are slower and rate-limited.</small></span>
        </label>
      </div>
    </section>

    <section class="card" data-provider-panel="anthropic" <?= $provider === 'openrouter' ? 'hidden' : '' ?>>
      <div class="card-head"><h2>Claude</h2></div>
      <?= form_field('password', 'anthropic_api_key', 'API key', '', [
          'placeholder' => $anthropicKey ?: 'sk-ant-…',
          'help'        => ($anthropicKey ? 'A key is saved. Leave this empty to keep it. ' : 'Create one at <a href="https://platform.claude.com/settings/keys" target="_blank" rel="noopener">platform.claude.com</a>. ') . 'The ANTHROPIC_API_KEY environment variable also works.',
          'attrs'       => ['autocomplete' => 'off'],
      ]) ?>
      <?php if ($anthropicKey): ?><?= form_field('checkbox', 'clear_anthropic_api_key', 'Remove the saved key', 0) ?><?php endif; ?>
      <div class="field-row">
        <?= form_field('select', 'anthropic_model', 'Model', setting('anthropic_model'), ['options' => anthropic_models()]) ?>
        <?= form_field('select', 'anthropic_effort', 'Effort', setting('anthropic_effort'), [
            'options' => ['low' => 'Low (fast, recommended for chat)', 'medium' => 'Medium', 'high' => 'High (slower, more thorough)'],
        ]) ?>
      </div>
      <p class="field-help">Opus 5.5, Sonnet 5.5 and Fable 5.1 requests include Anthropic's server-side fallback: if a request is declined on policy grounds, it is retried on a fallback model instead of failing. Haiku has no fallback.</p>
    </section>

    <section class="card" data-provider-panel="openrouter" <?= $provider === 'openrouter' ? '' : 'hidden' ?>>
      <div class="card-head"><h2>OpenRouter</h2></div>
      <?= form_field('password', 'openrouter_api_key', 'API key', '', [
          'placeholder' => $openrouterKey ?: 'sk-or-v1-…',
          'help'        => ($openrouterKey ? 'A key is saved. Leave this empty to keep it. ' : 'Create one at <a href="https://openrouter.ai/keys" target="_blank" rel="noopener">openrouter.ai/keys</a>. ') . 'The OPENROUTER_API_KEY environment variable also works.',
          'attrs'       => ['autocomplete' => 'off'],
      ]) ?>
      <?php if ($openrouterKey): ?><?= form_field('checkbox', 'clear_openrouter_api_key', 'Remove the saved key', 0) ?><?php endif; ?>
      <div class="field-row">
        <?= form_field('text', 'openrouter_model', 'Model', setting('openrouter_model'), ['error' => $errors['openrouter_model'] ?? null, 'placeholder' => 'google/gemma-4-26b-a4b-it:free']) ?>
        <?= form_field('text', 'openrouter_fallback_model', 'Fallback model', setting('openrouter_fallback_model'), ['help' => 'Used when the first model is busy or down.']) ?>
      </div>
      <?= form_field('number', 'openrouter_temperature', 'Temperature', setting('openrouter_temperature'), ['attrs' => ['step' => '0.1', 'min' => '0', 'max' => '1.5'], 'help' => 'Lower is more factual. 0.2–0.4 suits a support chat.']) ?>
    </section>

    <section class="card">
      <div class="card-head"><h2>Behaviour</h2></div>
      <?= form_field('textarea', 'ai_off_topic_reply', 'Reply to off-topic questions', setting('ai_off_topic_reply'), [
          'rows'  => 4,
          'error' => $errors['ai_off_topic_reply'] ?? null,
          'help'  => 'BRIX AI sends exactly this when a question has nothing to do with e-commerce or BRIX, or tries to change its instructions.',
      ]) ?>
      <?= form_field('textarea', 'ai_unavailable_reply', 'Reply when the AI service is down', setting('ai_unavailable_reply'), ['rows' => 3]) ?>
      <?= form_field('textarea', 'ai_extra_instructions', 'Extra instructions', setting('ai_extra_instructions'), [
          'rows'        => 5,
          'placeholder' => "e.g. Mention the 14-day free trial when someone asks about pricing.\nAlways answer in British English.",
          'help'        => 'Added to the end of the system prompt. Plain sentences work best.',
      ]) ?>
      <?= form_field('text', 'ai_assistant_name', 'Assistant name', setting('ai_assistant_name')) ?>
    </section>

    <section class="card">
      <div class="card-head"><h2>Chat window</h2></div>
      <?= form_field('text', 'chat_welcome_title', 'Empty-chat heading', setting('chat_welcome_title')) ?>
      <?= form_field('textarea', 'chat_welcome_text', 'Empty-chat text', setting('chat_welcome_text'), ['rows' => 2]) ?>
      <?= form_field('text', 'chat_disclaimer', 'Note under the chat box', setting('chat_disclaimer')) ?>
      <?= form_field('textarea', 'chat_answer_note', 'Note under each answer', setting('chat_answer_note'), ['rows' => 2, 'help' => 'Shown in small italics after every answer, except replies to off-topic questions. Leave empty to hide it.']) ?>
    </section>
  </div>

  <aside class="form-side">
    <section class="card">
      <div class="card-head"><h2>Retrieval</h2></div>
      <?= form_field('number', 'ai_context_chunks', 'Passages per question', setting('ai_context_chunks'), ['attrs' => ['min' => 1, 'max' => 12], 'help' => 'How many document passages the AI reads. 4–8 is a good range.']) ?>
      <?= form_field('code', 'kb_synonyms', 'Search synonyms', setting('kb_synonyms'), ['rows' => 10, 'help' => 'One per line: <code>term => extra words</code>. When a question contains the term, the extra words are searched too.']) ?>
      <a class="btn btn-sm" href="<?= e(url('/admin/kb/test')) ?>"><?= icon('search', 'icon icon-sm') ?>Test retrieval</a>
    </section>

    <section class="card">
      <div class="card-head"><h2>Limits</h2></div>
      <?= form_field('number', 'ai_max_tokens', 'Max answer length (tokens)', setting('ai_max_tokens'), ['attrs' => ['min' => 512, 'max' => 32000], 'help' => 'Includes the model\'s thinking. 4000 is plenty for chat.']) ?>
      <?= form_field('number', 'ai_history_messages', 'Earlier messages remembered', setting('ai_history_messages'), ['attrs' => ['min' => 0, 'max' => 20]]) ?>
      <?= form_field('number', 'ai_max_question_chars', 'Max question length (characters)', setting('ai_max_question_chars'), ['attrs' => ['min' => 100, 'max' => 4000]]) ?>
      <?= form_field('number', 'ai_rate_limit_per_hour', 'Questions per visitor per hour', setting('ai_rate_limit_per_hour'), ['attrs' => ['min' => 0, 'max' => 1000], 'help' => '0 turns the limit off.']) ?>
    </section>

    <button class="btn btn-primary btn-block" type="submit">Save AI settings</button>
  </aside>
</form>
