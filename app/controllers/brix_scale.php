<?php
declare(strict_types=1);

/**
 * Calendly booking for the BRIX Scale landing page (public/brix-scale/) ONLY.
 *
 * The landing page shows open times from Calendly and books the call with one tap, so the
 * visitor never sees Calendly's own "Schedule Event" screen. Nothing on the main BRIX site
 * or BRIX AI uses this. Settings live in Admin > BRIX Scale page > Calendly booking.
 *
 * If booking here is switched off, not set up, or fails for any reason, the landing page
 * falls back to the regular Calendly calendar, so a visitor can always book.
 */

use App\BrixScale\Calendly;
use App\BrixScale\CalendlyError;

const BRIX_SCALE_DAYS_AHEAD = 14;          // how far ahead the page offers times
const BRIX_SCALE_BOOKINGS_PER_IP_HOUR = 3; // more than this from one visitor falls back to Calendly's calendar

/* =========================================================
   Public API used by public/brix-scale/script.js
   ========================================================= */

/** GET /api/brix-scale/slots: open start times for the next two weeks (UTC). */
function api_brix_scale_slots(): void
{
    if (!same_origin()) {
        json_response(['enabled' => false], 403);
    }
    $client = brix_scale_client();
    $eventType = (string) setting('brix_scale_calendly_event_type');
    if (!$client || $eventType === '') {
        json_response(['enabled' => false]);
    }

    try {
        $from = new DateTimeImmutable('+5 minutes');
        $slots = $client->availableTimes($eventType, $from, $from->modify('+' . BRIX_SCALE_DAYS_AHEAD . ' days'));
    } catch (CalendlyError $ex) {
        log_message('brix-scale', 'Could not load Calendly times: ' . $ex->getMessage(), ['status' => $ex->status]);
        json_response(['enabled' => false]);
    }

    $meta = json_decode((string) setting('brix_scale_calendly_event_meta', '{}'), true) ?: [];
    json_response([
        'enabled'  => true,
        'slots'    => $slots,
        'duration' => (int) ($meta['duration'] ?? 0),
    ]);
}

/**
 * POST /api/brix-scale/book with JSON {start_time, name, email, phone, agency, clients, timezone, lead_id, utm_*}.
 * Answers {ok: true, start_time, reschedule_url, cancel_url} or {ok: false, code, message} where code is
 * "slot_taken" (pick another time) or "fallback" (finish on the Calendly calendar instead).
 */
function api_brix_scale_book(): void
{
    if (!same_origin()) {
        json_response(['ok' => false, 'code' => 'fallback', 'message' => 'Requests from other sites are not allowed.'], 403);
    }
    $body = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($body)) {
        json_response(['ok' => false, 'code' => 'fallback', 'message' => 'Bad request.'], 400);
    }

    $fallback = static fn (string $why) => json_response([
        'ok' => false, 'code' => 'fallback', 'message' => 'Pick your time on the calendar below to finish booking.', 'reason' => $why,
    ]);

    $client = brix_scale_client();
    $eventTypeUri = (string) setting('brix_scale_calendly_event_type');
    if (!$client || $eventTypeUri === '') {
        $fallback('not_configured');
    }

    // Visitor details, checked the same way the form checks them
    $lead = [
        'name'    => mb_substr(trim((string) ($body['name'] ?? '')), 0, 120),
        'email'   => mb_strtolower(trim((string) ($body['email'] ?? ''))),
        'phone'   => preg_replace('/[^\d+]/', '', (string) ($body['phone'] ?? '')),
        'agency'  => mb_substr(trim((string) ($body['agency'] ?? '')), 0, 200),
        'clients' => mb_substr(trim((string) ($body['clients'] ?? '')), 0, 200),
        'lead_id' => mb_substr(preg_replace('/[^a-zA-Z0-9-]/', '', (string) ($body['lead_id'] ?? '')), 0, 64),
    ];
    $timezone = brix_scale_timezone((string) ($body['timezone'] ?? ''));
    $startRaw = (string) ($body['start_time'] ?? '');

    if (mb_strlen($lead['name']) < 2 || !filter_var($lead['email'], FILTER_VALIDATE_EMAIL)
        || !preg_match('/^\+\d{7,15}$/', $lead['phone'])) {
        json_response(['ok' => false, 'code' => 'fallback', 'message' => 'Some details look wrong. Pick your time on the calendar below instead.'], 422);
    }
    try {
        $start = new DateTimeImmutable($startRaw);
    } catch (Exception) {
        json_response(['ok' => false, 'code' => 'slot_taken', 'message' => 'That time is not available. Pick another one.'], 422);
    }
    $startUtc = Calendly::utc($start);

    if (!brix_scale_table_ready()) {
        $fallback('table');
    }
    $ipHash = ip_hash();

    // The same person tapping twice: return the booking already made
    $existing = db_one(
        "SELECT * FROM brix_scale_bookings WHERE email = ? AND start_time = ? AND status = 'booked' AND created_at > ?",
        [$lead['email'], $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'), date('Y-m-d H:i:s', time() - 3600)]
    );
    if ($existing) {
        json_response(['ok' => true, 'start_time' => $startUtc, 'reschedule_url' => $existing['reschedule_url'], 'cancel_url' => $existing['cancel_url']]);
    }

    $recent = (int) db_val(
        "SELECT COUNT(*) FROM brix_scale_bookings WHERE ip_hash = ? AND status = 'booked' AND created_at > ?",
        [$ipHash, date('Y-m-d H:i:s', time() - 3600)]
    );
    if ($recent >= BRIX_SCALE_BOOKINGS_PER_IP_HOUR) {
        $fallback('rate_limited');
    }

    try {
        $eventType = $client->eventType($eventTypeUri);

        // Make sure the time is still open (Calendly has no "slot taken" error of its own)
        $open = $client->availableTimes($eventTypeUri, $start, $start->modify('+1 minute'));
        $stillOpen = false;
        foreach ($open as $time) {
            if (abs(strtotime($time) - $start->getTimestamp()) < 60) {
                $stillOpen = true;
                break;
            }
        }
        if (!$stillOpen) {
            json_response(['ok' => false, 'code' => 'slot_taken', 'message' => 'Someone just took that time. Pick another one.']);
        }

        $plan = brix_scale_booking_plan($eventType, $lead);
        if ($plan['problems']) {
            log_message('brix-scale', 'Booking here is not possible for this event type: ' . implode(' ', $plan['problems']));
            $fallback('event_type');
        }

        $request = [
            'event_type' => $eventTypeUri,
            'start_time' => $startUtc,
            'invitee'    => ['name' => $lead['name'], 'email' => $lead['email'], 'timezone' => $timezone],
            'tracking'   => [
                'utm_source'      => brix_scale_utm($body, 'utm_source') ?? 'brix-scale',
                'utm_medium'      => brix_scale_utm($body, 'utm_medium'),
                'utm_campaign'    => brix_scale_utm($body, 'utm_campaign'),
                'utm_content'     => $lead['lead_id'] !== '' ? $lead['lead_id'] : null, // matches the Google Sheet row
                'utm_term'        => brix_scale_utm($body, 'utm_term'),
                'salesforce_uuid' => null,
            ],
        ];
        if ($plan['location'] !== null) {
            $request['location'] = $plan['location'];
        }
        if ($plan['answers']) {
            $request['questions_and_answers'] = $plan['answers'];
        }

        $invitee = $client->createInvitee($request);
    } catch (CalendlyError $ex) {
        log_message('brix-scale', 'Calendly booking failed: ' . $ex->getMessage(), ['status' => $ex->status, 'details' => $ex->details]);
        brix_scale_record($lead, $start, $ipHash, 'failed', [], $ex->getMessage());
        $fallback($ex->status === 429 ? 'calendly_limit' : 'calendly_error');
    }

    brix_scale_record($lead, $start, $ipHash, 'booked', $invitee);
    json_response([
        'ok'             => true,
        'start_time'     => $startUtc,
        'invitee_uri'    => (string) ($invitee['uri'] ?? ''),
        'event_uri'      => (string) ($invitee['event'] ?? ''),
        'reschedule_url' => (string) ($invitee['reschedule_url'] ?? ''),
        'cancel_url'     => (string) ($invitee['cancel_url'] ?? ''),
    ]);
}

/* =========================================================
   Helpers
   ========================================================= */

function brix_scale_client(): ?Calendly
{
    return setting_on('brix_scale_booking_enabled') ? Calendly::fromSettings() : null;
}

/**
 * The visitor's time zone for Calendly's emails. Browsers sometimes report old names
 * (Chrome and Edge in India send "Asia/Calcutta"), so map those to today's name.
 */
function brix_scale_timezone(string $tz): string
{
    $renamed = [
        'Asia/Calcutta' => 'Asia/Kolkata', 'Asia/Katmandu' => 'Asia/Kathmandu', 'Asia/Saigon' => 'Asia/Ho_Chi_Minh',
        'Asia/Rangoon' => 'Asia/Yangon', 'Asia/Dacca' => 'Asia/Dhaka', 'Europe/Kiev' => 'Europe/Kyiv',
        'America/Buenos_Aires' => 'America/Argentina/Buenos_Aires', 'Pacific/Truk' => 'Pacific/Chuuk',
        'US/Eastern' => 'America/New_York', 'US/Central' => 'America/Chicago', 'US/Mountain' => 'America/Denver',
        'US/Pacific' => 'America/Los_Angeles', 'Asia/Ulan_Bator' => 'Asia/Ulaanbaatar', 'Atlantic/Faeroe' => 'Atlantic/Faroe',
    ];
    $tz = $renamed[$tz] ?? $tz;
    if (in_array($tz, timezone_identifiers_list(), true)) {
        return $tz;
    }
    try {
        return (new DateTimeZone($tz))->getName(); // another valid old name: Calendly generally accepts these
    } catch (Exception) {
        return 'UTC';
    }
}

function brix_scale_utm(array $body, string $key): ?string
{
    $value = trim((string) ($body[$key] ?? ''));
    return $value === '' ? null : mb_substr($value, 0, 255);
}

/**
 * How a booking for this event type will be filled in: which location to send and how each
 * Calendly question is answered from the form. "problems" lists anything that blocks booking here.
 */
function brix_scale_booking_plan(array $eventType, array $lead): array
{
    $problems = [];
    if (!empty($eventType['is_paid'])) {
        $problems[] = 'The event type takes payment, which Calendly does not allow through the API.';
    }

    // Location: Calendly needs one that is configured on the event type (none for round robin)
    $location = null;
    $locations = is_array($eventType['locations'] ?? null) ? $eventType['locations'] : [];
    if (($eventType['pooling_type'] ?? null) !== 'round_robin' && $locations) {
        $first = $locations[0];
        $kind = (string) ($first['kind'] ?? '');
        $location = match ($kind) {
            'outbound_call' => ['kind' => $kind, 'location' => $lead['phone']],
            'ask_invitee'   => ['kind' => $kind, 'location' => 'Phone / WhatsApp: ' . $lead['phone']],
            'physical', 'custom' => ($first['location'] ?? '') !== '' ? ['kind' => $kind, 'location' => (string) $first['location']] : null,
            default         => $kind !== '' ? ['kind' => $kind] : null,
        };
        if (in_array($kind, ['physical', 'custom'], true) && $location === null) {
            $problems[] = "The \"$kind\" location has no address saved in Calendly.";
        }
    }

    // Questions: the phone question gets the number; agency and client-count questions get those answers
    $answers = [];
    foreach ($eventType['custom_questions'] ?? [] as $q) {
        if (empty($q['enabled'])) {
            continue;
        }
        $answer = brix_scale_answer_for($q, $lead);
        if ($answer !== null && $answer !== '') {
            $answers[] = ['question' => (string) $q['name'], 'answer' => $answer, 'position' => (int) ($q['position'] ?? 0)];
        } elseif (!empty($q['required'])) {
            $problems[] = "The required question \"{$q['name']}\" can't be answered from the form.";
        }
    }

    return ['location' => $location, 'answers' => $answers, 'problems' => $problems];
}

/** The form's answer to one Calendly question, or null when the form doesn't ask it. */
function brix_scale_answer_for(array $question, array $lead): ?string
{
    $name = (string) ($question['name'] ?? '');
    $type = (string) ($question['type'] ?? '');
    if ($type === 'phone_number') {
        return $lead['phone'];
    }
    if (preg_match('/client|customer|store/i', $name)) {
        return $lead['clients'];   // checked before "number" so "Number of clients" isn't taken as a phone question
    }
    if (preg_match('/agency|company|business|brand/i', $name)) {
        return $lead['agency'];
    }
    if (preg_match('/phone|whats\s*app|mobile|contact number/i', $name)) {
        return $lead['phone'];
    }
    return null;
}

/** Creates the bookings log table on first use, so an existing database needs no manual step. */
function brix_scale_table_ready(): bool
{
    static $ready = null;
    if ($ready === null) {
        try {
            db_exec(
                "CREATE TABLE IF NOT EXISTS brix_scale_bookings (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    lead_id VARCHAR(64) NOT NULL DEFAULT '',
                    name VARCHAR(120) NOT NULL DEFAULT '',
                    email VARCHAR(190) NOT NULL,
                    phone VARCHAR(32) NOT NULL DEFAULT '',
                    start_time DATETIME NOT NULL,
                    status VARCHAR(16) NOT NULL,
                    invitee_uri VARCHAR(255) NOT NULL DEFAULT '',
                    event_uri VARCHAR(255) NOT NULL DEFAULT '',
                    reschedule_url VARCHAR(255) NOT NULL DEFAULT '',
                    cancel_url VARCHAR(255) NOT NULL DEFAULT '',
                    error VARCHAR(500) NULL,
                    ip_hash CHAR(64) NOT NULL DEFAULT '',
                    created_at DATETIME NOT NULL,
                    KEY idx_bsb_ip (ip_hash, created_at),
                    KEY idx_bsb_email (email, start_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $ready = true;
        } catch (Throwable $ex) {
            log_message('brix-scale', 'Could not create brix_scale_bookings: ' . $ex->getMessage());
            $ready = false;
        }
    }
    return $ready;
}

function brix_scale_record(array $lead, DateTimeImmutable $start, string $ipHash, string $status, array $invitee, ?string $error = null): void
{
    try {
        db_insert('brix_scale_bookings', [
            'lead_id'        => $lead['lead_id'],
            'name'           => $lead['name'],
            'email'          => $lead['email'],
            'phone'          => $lead['phone'],
            'start_time'     => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'status'         => $status,
            'invitee_uri'    => mb_substr((string) ($invitee['uri'] ?? ''), 0, 255),
            'event_uri'      => mb_substr((string) ($invitee['event'] ?? ''), 0, 255),
            'reschedule_url' => mb_substr((string) ($invitee['reschedule_url'] ?? ''), 0, 255),
            'cancel_url'     => mb_substr((string) ($invitee['cancel_url'] ?? ''), 0, 255),
            'error'          => $error !== null ? mb_substr($error, 0, 500) : null,
            'ip_hash'        => $ipHash,
            'created_at'     => now(),
        ]);
    } catch (Throwable $ex) {
        log_message('brix-scale', 'Could not record booking: ' . $ex->getMessage());
    }
}

/* =========================================================
   Admin: BRIX Scale page > Calendly booking
   ========================================================= */

function admin_brix_scale(): void
{
    require_admin('admin');
    $errors = [];

    if (is_post()) {
        verify_csrf();
        $values = ['brix_scale_booking_enabled' => (string) input_bool('brix_scale_booking_enabled')];

        // A token field left empty keeps the saved token; the remove box clears it.
        $newToken = trim((string) ($_POST['brix_scale_calendly_token'] ?? ''));
        $tokenChanged = false;
        if (!empty($_POST['clear_brix_scale_calendly_token'])) {
            $values['brix_scale_calendly_token'] = '';
            $values['brix_scale_calendly_event_type'] = '';
            $values['brix_scale_calendly_event_meta'] = '{}';
            $values['brix_scale_booking_enabled'] = '0';
            $tokenChanged = true;
        } elseif ($newToken !== '') {
            try {
                (new Calendly($newToken))->me();
                $values['brix_scale_calendly_token'] = $newToken;
                $tokenChanged = true;
            } catch (CalendlyError $ex) {
                $errors['brix_scale_calendly_token'] = 'Calendly did not accept this token: ' . $ex->getMessage();
            }
        }

        $eventType = trim((string) ($_POST['brix_scale_calendly_event_type'] ?? ''));
        if (!$tokenChanged || $newToken !== '') {
            if ($eventType !== '' && !preg_match('#^https://api\.calendly\.com/event_types/[A-Za-z0-9-]+$#', $eventType)) {
                $errors['brix_scale_calendly_event_type'] = 'Pick an event type from the list.';
            } elseif ($eventType !== '' && !$errors) {
                try {
                    $client = new Calendly($values['brix_scale_calendly_token'] ?? (string) setting('brix_scale_calendly_token'));
                    $type = $client->eventType($eventType);
                    $values['brix_scale_calendly_event_type'] = $eventType;
                    $values['brix_scale_calendly_event_meta'] = json_encode([
                        'name'           => (string) ($type['name'] ?? ''),
                        'duration'       => (int) ($type['duration'] ?? 0),
                        'scheduling_url' => (string) ($type['scheduling_url'] ?? ''),
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } catch (CalendlyError $ex) {
                    $errors['brix_scale_calendly_event_type'] = 'Could not load this event type from Calendly: ' . $ex->getMessage();
                }
            } elseif ($eventType === '') {
                $values['brix_scale_calendly_event_type'] = '';
                $values['brix_scale_calendly_event_meta'] = '{}';
            }
        }

        $willHaveToken = ($values['brix_scale_calendly_token'] ?? (string) setting('brix_scale_calendly_token')) !== '';
        $willHaveType = ($values['brix_scale_calendly_event_type'] ?? (string) setting('brix_scale_calendly_event_type')) !== '';
        if ($values['brix_scale_booking_enabled'] === '1' && (!$willHaveToken || !$willHaveType) && !$errors) {
            $errors['brix_scale_booking_enabled'] = 'Save a token and pick an event type before switching this on.';
        }

        if (!$errors) {
            settings_save($values);
            flash('success', 'BRIX Scale booking settings saved.');
            redirect('/admin/brix-scale');
        }
        remember_input();
    }

    // What Calendly says about the saved token and event type
    $account = null;
    $eventTypes = [];
    $plan = null;
    $connectionError = null;
    $client = Calendly::fromSettings();
    if ($client) {
        try {
            $account = $client->me();
            $eventTypes = $client->eventTypes((string) ($account['uri'] ?? ''));
            $selected = (string) setting('brix_scale_calendly_event_type');
            if ($selected !== '') {
                $type = $client->eventType($selected);
                $sample = ['phone' => '+919876543210', 'agency' => 'Acme Growth Co.', 'clients' => '6 to 10 clients'];
                $plan = brix_scale_booking_plan($type, $sample) + ['type' => $type];
            }
        } catch (CalendlyError $ex) {
            $connectionError = $ex->getMessage();
        }
    }

    render('admin/brix_scale', compact('errors', 'account', 'eventTypes', 'plan', 'connectionError') + [
        'title' => 'BRIX Scale: Calendly booking',
        'recent' => brix_scale_table_ready()
            ? db_all('SELECT * FROM brix_scale_bookings ORDER BY id DESC LIMIT 15')
            : [],
    ], 'admin/layout');
    forget_input();
}
