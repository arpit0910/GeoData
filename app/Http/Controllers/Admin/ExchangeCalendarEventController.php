<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeCalendarEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExchangeCalendarEventController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'exchange' => ['nullable', Rule::in(['NSE', 'BSE', ExchangeCalendarEvent::EXCHANGE_BOTH])],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'type' => ['nullable', Rule::in(['holiday', 'muhurat', 'weekend'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $events = ExchangeCalendarEvent::query()
            ->when($filters['exchange'] ?? null, function ($query, $exchange) {
                return in_array($exchange, ['NSE', 'BSE'], true)
                    ? $query->whereIn('exchange', [$exchange, ExchangeCalendarEvent::EXCHANGE_BOTH])
                    : $query->where('exchange', $exchange);
            })
            ->when($filters['year'] ?? null, fn ($query, $year) => $query->whereYear('event_date', $year))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('event_type', $type))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('event_date')
            ->orderBy('exchange')
            ->paginate(25)
            ->withQueryString();

        $years = ExchangeCalendarEvent::query()
            ->orderByDesc('event_date')
            ->get(['event_date'])
            ->map(fn (ExchangeCalendarEvent $event) => $event->event_date->year)
            ->unique()
            ->values();

        return view('admin.exchange-calendar.index', compact('events', 'years', 'filters'));
    }

    public function create(): View
    {
        return view('admin.exchange-calendar.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data = array_merge($data, [
            'source' => 'manual',
            'source_url' => 'admin://manual',
            'source_payload' => ['created_by' => $request->user()->id],
            'is_manually_overridden' => true,
            'synced_at' => now(),
        ]);

        $event = ExchangeCalendarEvent::create($data);

        return redirect()->route('admin.exchange-calendar.show', $event)
            ->with('success', 'Exchange calendar event created successfully.');
    }

    public function show(ExchangeCalendarEvent $exchangeCalendar): View
    {
        return view('admin.exchange-calendar.show', ['event' => $exchangeCalendar]);
    }

    public function edit(ExchangeCalendarEvent $exchangeCalendar): View
    {
        return view('admin.exchange-calendar.edit', ['event' => $exchangeCalendar]);
    }

    public function update(Request $request, ExchangeCalendarEvent $exchangeCalendar): RedirectResponse
    {
        $data = $this->validated($request, $exchangeCalendar);
        $payload = $exchangeCalendar->source_payload ?? [];
        $payload['last_edited_by'] = $request->user()->id;
        $payload['last_edited_at'] = now()->toIso8601String();

        $exchangeCalendar->update(array_merge($data, [
            'source' => 'manual',
            'source_url' => 'admin://manual-override',
            'source_payload' => $payload,
            'is_manually_overridden' => true,
            'synced_at' => now(),
        ]));

        return redirect()->route('admin.exchange-calendar.show', $exchangeCalendar)
            ->with('success', 'Exchange calendar event updated successfully.');
    }

    public function destroy(ExchangeCalendarEvent $exchangeCalendar): RedirectResponse
    {
        $exchangeCalendar->delete();

        return redirect()->route('admin.exchange-calendar.index')
            ->with('success', 'Exchange calendar event deleted successfully.');
    }

    private function validated(Request $request, ?ExchangeCalendarEvent $event = null): array
    {
        $uniqueDate = Rule::unique('exchange_calendar_events', 'event_date')
            ->where(fn ($query) => $query
                ->where('exchange', $request->input('exchange'))
                ->where('segment', $request->input('segment', 'equity')));

        if ($event) {
            $uniqueDate->ignore($event->id);
        }

        return $request->validate([
            'exchange' => ['required', Rule::in(['NSE', 'BSE', ExchangeCalendarEvent::EXCHANGE_BOTH])],
            'segment' => ['required', Rule::in(['equity'])],
            'event_date' => ['required', 'date_format:Y-m-d', $uniqueDate],
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', Rule::in(['holiday', 'muhurat', 'weekend'])],
            'session_start' => ['nullable', 'date_format:H:i'],
            'session_end' => ['nullable', 'date_format:H:i', 'after:session_start'],
        ], [
            'event_date.unique' => 'An event already exists for this exchange, segment, and date.',
        ]);
    }
}
