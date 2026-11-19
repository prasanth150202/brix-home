<?php
/**
 * Admin > BRIX Scale page > Calendly booking.
 * Only affects the BRIX Scale landing page at /brix-scale/. The main site and BRIX AI don't use any of it.
 *
 * @var array $errors
 * @var ?array $account
 * @var array $eventTypes
 * @var ?array $plan
 * @var ?string $connectionError
 * @var array $recent
 */
$token = masked_key('brix_scale_calendly_token');
$selected = (string) setting('brix_scale_calendly_event_type');
$enabled = setting_on('brix_scale_booking_enabled');
$typeOptions = ['' => $eventTypes ? 'Choose an event type…' : 'Save a token first'];
foreach ($eventTypes as $type) {
    $typeOptions[(string) $type['uri']] = (string) ($type['name'] ?? 'Untitled') . ' (' . (int) ($type['duration'] ?? 0) . ' min)';
}
if ($selected !== '' && !isset($typeOptions[$selected])) {
    $typeOptions[$selected] = 'Saved event type (not in your active list)';
}
$locationLabels = [
    'zoom_conference' => 'Zoom', 'google_conference' => 'Google Meet', 'microsoft_teams_conference' => 'Microsoft Teams',
    'gotomeeting_conference' => 'GoTo Meeting', 'webex_conference' => 'Webex', 'outbound_call' => 'You call them (their number from the form)',
    'inbound_call' => 'They call you', 'ask_invitee' => 'Asked of the invitee (their number is sent)', 'physical' => 'In person', 'custom' => 'Custom',
];
?>
<div class="alert alert-warning" style="margin-bottom:20px">
  These settings are for the <strong>BRIX Scale landing page only</strong> (<a href="<?= e(url('/brix-scale/')) ?>" target="_blank" rel="noopener">/brix-scale/</a>).
  The main BRIX website and the BRIX AI chat do not use this Calendly token or anything on this page.
</div>

<form method="post" class="form-layout">
  <?= csrf_field() ?>
  <div class="form-main">
    <section class="card">
      <div class="card-head"><h2>Book calls inside the BRIX Scale page</h2></div>
      <p class="field-help" style="margin-top:0">
        When this is on, visitors who finish the application form on the BRIX Scale page see open times and book with one tap.
        They never see Calendly's own "Schedule Event" screen. Calendly still sends the usual confirmation email and calendar invite.
        When it's off, or if anything goes wrong, the page shows the regular Calendly calendar instead, so visitors can always book.
      </p>
      <?= form_field('checkbox', 'brix_scale_booking_enabled', 'Book calls inside the BRIX Scale page', $enabled, [
          'error' => $errors['brix_scale_booking_enabled'] ?? null,
      ]) ?>
    </section>

    <section class="card">
      <div class="card-head"><h2>Calendly connection</h2></div>
      <?= form_field('password', 'brix_scale_calendly_token', 'Calendly personal access token', '', [
          'placeholder' => $token ?: 'Paste your token here',
          'error'       => $errors['brix_scale_calendly_token'] ?? null,
          'help'        => ($token ? 'A token is saved. Leave this empty to keep it. ' : '')
              . 'Create one in Calendly under <a href="https://calendly.com/integrations/api_webhooks" target="_blank" rel="noopener">Integrations &rarr; API and webhooks</a>. '
              . 'It is stored on the server and never sent to visitors. Used only by the BRIX Scale page.',
          'attrs'       => ['autocomplete' => 'off'],
      ]) ?>
      <?php if ($token): ?><?= form_field('checkbox', 'clear_brix_scale_calendly_token', 'Remove the saved token (also switches booking off)', 0) ?><?php endif; ?>

      <?php if ($connectionError): ?>
        <div class="alert alert-error">Calendly rejected the saved token: <?= e($connectionError) ?></div>
      <?php elseif ($account): ?>
        <div class="alert alert-success">Connected to Calendly as <strong><?= e((string) ($account['name'] ?? '')) ?></strong> (<?= e((string) ($account['email'] ?? '')) ?>).</div>
      <?php endif; ?>

      <?= form_field('select', 'brix_scale_calendly_event_type', 'Event type to book', $selected, [
          'options' => $typeOptions,
          'error'   => $errors['brix_scale_calendly_event_type'] ?? null,
          'help'    => 'The meeting the BRIX Scale page books. Pick the same one the page used before (e.g. "BRIX Agency Product Demo").',
      ]) ?>
    </section>

    <?php if ($plan): $type = $plan['type']; ?>
    <section class="card">
      <div class="card-head"><h2>How bookings will be filled in</h2></div>
      <p class="field-help" style="margin-top:0">
        <strong><?= e((string) ($type['name'] ?? '')) ?></strong>, <?= (int) ($type['duration'] ?? 0) ?> minutes.
        Location: <?php $kind = (string) ($plan['location']['kind'] ?? ''); ?><?= e($kind !== '' ? ($locationLabels[$kind] ?? $kind) : 'none sent') ?>.
      </p>
      <table class="table">
        <thead><tr><th>Calendly question</th><th>Answered with</th></tr></thead>
        <tbody>
          <?php foreach ($type['custom_questions'] ?? [] as $q): if (empty($q['enabled'])) continue; ?>
          <?php $answer = brix_scale_answer_for($q, ['phone' => 'phone', 'agency' => 'agency', 'clients' => 'clients']); ?>
          <tr>
            <td><?= e((string) $q['name']) ?><?= !empty($q['required']) ? ' <span class="badge">required</span>' : '' ?></td>
            <td>
              <?php if ($answer === 'phone'): ?><span class="badge badge-green">Number from the form</span>
              <?php elseif ($answer === 'agency'): ?><span class="badge badge-green">Agency name</span>
              <?php elseif ($answer === 'clients'): ?><span class="badge badge-green">Client count</span>
              <?php elseif (!empty($q['required'])): ?><span class="badge badge-red">Can't be answered: booking falls back to Calendly</span>
              <?php else: ?><span class="badge">Left blank (optional)</span><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($plan['problems']): ?>
        <div class="alert alert-error" style="margin-top:16px">
          Booking inside the page won't work with this event type, so visitors will get the Calendly calendar:
          <ul><?php foreach ($plan['problems'] as $problem): ?><li><?= e($problem) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="card">
      <div class="card-head"><h2>Recent BRIX Scale bookings</h2></div>
      <?php if (!$recent): ?>
        <p class="field-help" style="margin:0">No bookings made through the BRIX Scale page yet.</p>
      <?php else: ?>
      <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Call time (UTC)</th><th>Result</th><th>Made</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $row): ?>
          <tr>
            <td><?= e($row['name']) ?></td>
            <td><?= e($row['email']) ?></td>
            <td><?= e(format_date($row['start_time'], 'M j, H:i')) ?></td>
            <td>
              <?php if ($row['status'] === 'booked'): ?><span class="badge badge-green">Booked</span>
              <?php else: ?><span class="badge badge-amber" title="<?= e((string) $row['error']) ?>">Sent to Calendly calendar</span><?php endif; ?>
            </td>
            <td><?= e(time_ago($row['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </section>
  </div>

  <aside class="form-side">
    <section class="card">
      <div class="card-head"><h2>Good to know</h2></div>
      <ul class="field-help" style="margin:0;padding-left:18px;display:grid;gap:8px">
        <li>Only the BRIX Scale page uses this. The main site is unaffected.</li>
        <li>Calendly's <strong>Free plan allows 5 bookings a day</strong> through this connection. After that, visitors automatically get the Calendly calendar for the rest of the day. Paid plans allow 100 a day.</li>
        <li>Event types that take payment can't be booked this way.</li>
        <li>If someone else takes a time first, the visitor is asked to pick another.</li>
      </ul>
    </section>
    <button class="btn btn-primary btn-block" type="submit">Save</button>
  </aside>
</form>
