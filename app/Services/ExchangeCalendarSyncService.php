<?php

namespace App\Services;

use App\Models\ExchangeCalendarEvent;
use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExchangeCalendarSyncService
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126 Safari/537.36';

    public function sync(string $exchange, int $year): array
    {
        $exchange = strtoupper($exchange);
        $events = match ($exchange) {
            'NSE' => $this->fetchNse($year),
            'BSE' => $this->fetchBse($year),
            default => throw new RuntimeException("Unsupported exchange: {$exchange}"),
        };

        // Exchanges commonly publish the next year's calendar late in the
        // current year. An empty year is therefore a safe no-op, never a
        // signal to erase previously synchronized records.
        if ($events === []) {
            return ['exchange' => $exchange, 'year' => $year, 'synced' => 0, 'skipped' => true];
        }

        return $this->persist($events, $exchange, $year, strtolower($exchange), [$exchange]);
    }

    public function syncCombined(int $year): array
    {
        $nseEvents = $this->fetchNse($year);
        $bseEvents = $this->fetchBse($year);

        if ($nseEvents === [] && $bseEvents === []) {
            return ['exchange' => ExchangeCalendarEvent::EXCHANGE_BOTH, 'year' => $year, 'synced' => 0, 'skipped' => true];
        }

        $eventsByDate = [];
        $date = Carbon::create($year, 1, 1, 0, 0, 0, config('exchange_calendar.timezone'));
        while ($date->year === $year) {
            if ($date->isWeekend()) {
                $eventsByDate[$date->toDateString()] = [
                    'event_date' => $date->toDateString(),
                    'name' => $date->format('l'),
                    'event_type' => ExchangeCalendarEvent::TYPE_WEEKEND,
                    'source_url' => 'calendar://weekend',
                    'source_payload' => ['generated' => true, 'day' => $date->format('l')],
                ];
            }
            $date->addDay();
        }

        foreach (['NSE' => $nseEvents, 'BSE' => $bseEvents] as $provider => $providerEvents) {
            foreach ($providerEvents as $event) {
                $eventDate = $event['event_date'];
                if (! isset($eventsByDate[$eventDate]) || $eventsByDate[$eventDate]['event_type'] === ExchangeCalendarEvent::TYPE_WEEKEND) {
                    $eventsByDate[$eventDate] = $event + ['providers' => []];
                }

                $eventsByDate[$eventDate]['providers'][$provider] = [
                    'name' => $event['name'],
                    'event_type' => $event['event_type'],
                    'source_url' => $event['source_url'],
                    'source_payload' => $event['source_payload'] ?? null,
                ];
                if ($event['event_type'] === ExchangeCalendarEvent::TYPE_MUHURAT) {
                    $eventsByDate[$eventDate]['event_type'] = ExchangeCalendarEvent::TYPE_MUHURAT;
                }
            }
        }

        foreach ($eventsByDate as &$event) {
            if (empty($event['providers'])) {
                continue;
            }
            $providerRows = $event['providers'];
            $names = array_values(array_unique(array_column($providerRows, 'name')));
            $urls = array_values(array_unique(array_column($providerRows, 'source_url')));
            $event['name'] = implode(' / ', $names);
            $event['source_url'] = implode(', ', $urls);
            $event['source_payload'] = ['providers' => $providerRows];
            unset($event['providers']);
        }
        unset($event);

        ksort($eventsByDate);

        return $this->persist(
            array_values($eventsByDate),
            ExchangeCalendarEvent::EXCHANGE_BOTH,
            $year,
            'nse_bse',
            ['NSE', 'BSE', ExchangeCalendarEvent::EXCHANGE_BOTH]
        );
    }

    private function persist(array $events, string $exchange, int $year, string $source, array $cleanupExchanges): array
    {
        $segment = config('exchange_calendar.segment', 'equity');
        $now = now();

        DB::transaction(function () use ($events, $exchange, $source, $segment, $year, $now, $cleanupExchanges): void {
            $dates = [];
            foreach ($events as $event) {
                $dates[] = $event['event_date'];
                $existingEvent = ExchangeCalendarEvent::query()->where([
                    'exchange' => $exchange,
                    'segment' => $segment,
                    'event_date' => $event['event_date'],
                ])->first();

                if ($existingEvent?->is_manually_overridden) {
                    continue;
                }

                ExchangeCalendarEvent::updateOrCreate(
                    [
                        'exchange' => $exchange,
                        'segment' => $segment,
                        'event_date' => $event['event_date'],
                    ],
                    [
                        'name' => $event['name'],
                        'event_type' => $event['event_type'],
                        'session_start' => $event['session_start'] ?? null,
                        'session_end' => $event['session_end'] ?? null,
                        'source' => $source,
                        'source_url' => $event['source_url'],
                        'source_payload' => $event['source_payload'] ?? null,
                        'synced_at' => $now,
                    ]
                );
            }

            $existing = ExchangeCalendarEvent::query()
                ->whereIn('exchange', $cleanupExchanges)
                ->where('segment', $segment)
                ->where('is_manually_overridden', false)
                ->get()
                ->filter(fn (ExchangeCalendarEvent $event) => $event->event_date->year === $year);

            $existing
                ->filter(fn (ExchangeCalendarEvent $event) => $event->exchange !== $exchange
                    || ! in_array($event->event_date->toDateString(), $dates, true))
                ->each->delete();
        });

        return ['exchange' => $exchange, 'year' => $year, 'synced' => count($events), 'skipped' => false];
    }

    private function fetchNse(int $year): array
    {
        $url = config('exchange_calendar.nse.url');
        $cookies = new CookieJar();
        $response = $this->request('https://www.nseindia.com/', $cookies)->get($url);

        if (in_array($response->status(), [401, 403, 429], true)) {
            $this->request('https://www.nseindia.com/', $cookies)->get('https://www.nseindia.com/');
            $response = $this->request(config('exchange_calendar.nse.page_url'), $cookies)->get($url);
        }

        $response->throw();
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('NSE returned an invalid holiday response.');
        }

        $rows = $payload['CM'] ?? $payload['cm'] ?? $payload['data'] ?? [];
        if (! is_array($rows)) {
            throw new RuntimeException('NSE holiday response does not contain the equity (CM) calendar.');
        }

        return $this->normalizeRows($rows, $year, 'NSE', $url);
    }

    private function fetchBse(int $year): array
    {
        $primaryUrl = config("exchange_calendar.bse.year_urls.{$year}")
            ?: config('exchange_calendar.bse.url');
        $urls = array_values(array_unique(array_filter(array_merge(
            [$primaryUrl],
            config("exchange_calendar.bse.supplemental_urls.{$year}", [])
        ))));
        $events = [];
        $parsedRows = 0;

        foreach ($urls as $url) {
            $response = $this->request('https://www.bseindia.com/')->accept('text/html')->get($url);
            $response->throw();

            $html = $response->body();
            $rows = $this->parseBseTables($html);
            $parsedRows += count($rows);
            foreach (array_merge(
                $this->normalizeRows($rows, $year, 'BSE', $url),
                $this->parseMuhuratNotice($html, $year, $url),
                $this->parseClosureNotice($html, $year, $url)
            ) as $event) {
                $events[$event['event_date']] = $event;
            }
        }

        if ($events === [] && $parsedRows === 0) {
            throw new RuntimeException('BSE holiday page contained no parseable equity calendar rows.');
        }

        ksort($events);
        return array_values($events);
    }

    private function parseClosureNotice(string $html, int $year, string $url): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html))));
        if (! preg_match('/Equity Segment/i', $text)
            || ! preg_match('/remain closed on\s+([A-Za-z]+\s+\d{1,2},\s*\d{4})/i', $text, $dateMatch)) {
            return [];
        }

        $date = $this->parseDate($dateMatch[1]);
        if (! $date || $date->year !== $year) {
            return [];
        }

        preg_match('/Subject\s+(.*?)\s+Content\b/is', $text, $subjectMatch);
        $name = trim($subjectMatch[1] ?? 'Trading Holiday');

        return [[
            'event_date' => $date->toDateString(),
            'name' => $name,
            'event_type' => ExchangeCalendarEvent::TYPE_HOLIDAY,
            'source_url' => $url,
            'source_payload' => ['notice' => $name, 'closure_date' => $dateMatch[1]],
        ]];
    }

    private function parseBseTables(string $html): array
    {
        $rows = [];
        if (! class_exists(\DOMDocument::class)) {
            throw new RuntimeException('The PHP DOM extension is required to parse the BSE holiday page.');
        }

        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($document);

        foreach ($xpath->query('//tr') as $tr) {
            $cells = [];
            foreach ($xpath->query('./th|./td', $tr) as $cell) {
                $cells[] = trim(preg_replace('/\s+/u', ' ', $cell->textContent));
            }
            if (count($cells) < 3) {
                continue;
            }

            $date = null;
            $dateIndex = null;
            foreach ($cells as $index => $cell) {
                $date = $this->parseDate($cell);
                if ($date !== null) {
                    $dateIndex = $index;
                    break;
                }
            }
            if ($date === null) {
                continue;
            }

            $nameCandidates = array_values(array_filter($cells, function ($cell, $index) use ($dateIndex) {
                return $index !== $dateIndex
                    && ! preg_match('/^\d+$/', $cell)
                    && ! preg_match('/^(mon|tues|wednes|thurs|fri|satur|sun)day$/i', $cell)
                    && ! preg_match('/^(remains?\s+closed|open)$/i', $cell);
            }, ARRAY_FILTER_USE_BOTH));

            if ($nameCandidates !== []) {
                $rows[] = ['date' => $date->toDateString(), 'name' => $nameCandidates[0], 'cells' => $cells];
            }
        }

        return $rows;
    }

    private function normalizeRows(array $rows, int $year, string $exchange, string $url): array
    {
        $events = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $dateValue = $row['tradingDate'] ?? $row['date'] ?? $row['Date'] ?? $row['HolidayDate'] ?? null;
            $name = trim((string) ($row['description'] ?? $row['name'] ?? $row['Holiday'] ?? $row['holiday'] ?? 'Trading Holiday'));
            $date = $this->parseDate((string) $dateValue);
            if ($date === null || $date->year !== $year) {
                continue;
            }

            $type = $this->isMuhurat($name) ? ExchangeCalendarEvent::TYPE_MUHURAT : ExchangeCalendarEvent::TYPE_HOLIDAY;
            $events[$date->toDateString()] = [
                'event_date' => $date->toDateString(),
                'name' => $name,
                'event_type' => $type,
                'source_url' => $url,
                'source_payload' => $row,
            ];
        }

        ksort($events);
        return $events;
    }

    private function parseMuhuratNotice(string $html, int $year, string $url): array
    {
        $text = html_entity_decode(strip_tags($html));
        if (! preg_match_all('/Muhurat\s+Trading.{0,180}?(\d{1,2}[\/-][A-Za-z]{3,9}[\/-]\d{2,4}|[A-Za-z]{3,9}\s+\d{1,2},?\s+\d{4}|\d{1,2}\s+[A-Za-z]{3,9}\s+\d{4})/is', $text, $matches)) {
            return [];
        }

        $events = [];
        foreach ($matches[1] as $value) {
            $date = $this->parseDate($value);
            if ($date && $date->year === $year) {
                $events[] = [
                    'event_date' => $date->toDateString(),
                    'name' => 'Muhurat Trading',
                    'event_type' => ExchangeCalendarEvent::TYPE_MUHURAT,
                    'source_url' => $url,
                    'source_payload' => ['notice' => trim($matches[0][array_search($value, $matches[1], true)])],
                ];
            }
        }
        return $events;
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value));
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd-M-Y', 'd-M-y', 'd/m/Y', 'd/m/y', 'F d, Y', 'F d,Y', 'M d, Y', 'd F Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
                if ($date !== false && $date->format($format) === $value) {
                    return $date;
                }
            } catch (\Throwable) {
                // Try the next known exchange format.
            }
        }

        return null;
    }

    private function isMuhurat(string $name): bool
    {
        return (bool) preg_match('/muhurat|laxmi\s+pujan/i', $name);
    }

    private function request(string $referer, ?CookieJar $cookies = null): PendingRequest
    {
        $verify = config('exchange_calendar.ca_bundle') ?: config('exchange_calendar.verify_tls', true);

        return Http::withHeaders([
            'User-Agent' => self::USER_AGENT,
            'Accept-Language' => 'en-US,en;q=0.9',
            'Referer' => $referer,
        ])->withOptions(array_filter([
            'verify' => $verify,
            'cookies' => $cookies,
        ], fn ($value) => $value !== null))
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(2, 500, null, false);
    }
}
