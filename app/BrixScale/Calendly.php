<?php
declare(strict_types=1);

namespace App\BrixScale;

/**
 * Calendly API client for the BRIX Scale landing page (public/brix-scale/) only.
 * The main BRIX site and BRIX AI do not use it.
 *
 * Docs: https://developer.calendly.com (Personal Access Token, bearer auth).
 */
final class Calendly
{
    private const BASE = 'https://api.calendly.com';

    public function __construct(private string $token)
    {
    }

    /** A client for the token saved in Admin > BRIX Scale > Calendly booking, or null when none is saved. */
    public static function fromSettings(): ?self
    {
        $token = trim((string) setting('brix_scale_calendly_token'));
        return $token === '' ? null : new self($token);
    }

    public function me(): array
    {
        return $this->request('GET', '/users/me')['resource'] ?? [];
    }

    /** Active event types the token's user owns, sorted by name. */
    public function eventTypes(string $userUri): array
    {
        $types = [];
        $pageToken = null;
        do {
            $query = ['user' => $userUri, 'active' => 'true', 'count' => 100];
            if ($pageToken) {
                $query['page_token'] = $pageToken;
            }
            $page = $this->request('GET', '/event_types', $query);
            $types = array_merge($types, $page['collection'] ?? []);
            $pageToken = $page['pagination']['next_page_token'] ?? null;
        } while ($pageToken && count($types) < 500);
        return $types;
    }

    public function eventType(string $uri): array
    {
        return $this->request('GET', $this->path($uri))['resource'] ?? [];
    }

    /** Open start times (UTC, ISO 8601) between two moments. Calendly allows at most 31 days per call. */
    public function availableTimes(string $eventTypeUri, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $page = $this->request('GET', '/event_type_available_times', [
            'event_type' => $eventTypeUri,
            'start_time' => self::utc($from),
            'end_time'   => self::utc($to),
        ]);
        $times = [];
        foreach ($page['collection'] ?? [] as $slot) {
            if (($slot['status'] ?? '') === 'available' && !empty($slot['start_time'])) {
                $times[] = (string) $slot['start_time'];
            }
        }
        sort($times);
        return $times;
    }

    /** Books the invitee. Returns the invitee resource (uri, event, cancel_url, reschedule_url, ...). */
    public function createInvitee(array $body): array
    {
        return $this->request('POST', '/invitees', [], $body)['resource'] ?? [];
    }

    public static function utc(\DateTimeInterface $at): string
    {
        return \DateTimeImmutable::createFromInterface($at)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.000000\Z');
    }

    private function path(string $uri): string
    {
        return str_starts_with($uri, self::BASE) ? substr($uri, strlen(self::BASE)) : $uri;
    }

    private function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        $url = self::BASE . $path . ($query ? '?' . http_build_query($query) : '');
        $ch = curl_init($url);
        $headers = ['Authorization: Bearer ' . $this->token, 'Accept: application/json'];
        $options = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 20,
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $options[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $options);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new CalendlyError('Could not reach Calendly: ' . $curlError, 0);
        }
        $json = json_decode((string) $raw, true);
        if ($status >= 400) {
            $title = is_array($json) ? (string) ($json['title'] ?? '') : '';
            $message = is_array($json) ? (string) ($json['message'] ?? '') : '';
            $details = is_array($json) && !empty($json['details']) ? $json['details'] : [];
            throw new CalendlyError(trim($title . ': ' . $message, ': ') ?: "Calendly returned HTTP $status", $status, $details);
        }
        return is_array($json) ? $json : [];
    }
}
