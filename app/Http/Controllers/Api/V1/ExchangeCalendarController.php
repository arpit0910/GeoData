<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExchangeCalendarEvent;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExchangeCalendarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'exchange' => ['nullable', Rule::in(['nse', 'bse', 'all'])],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'type' => ['nullable', Rule::in(['holiday', 'muhurat', 'weekend'])],
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $exchange = strtolower((string) $request->input('exchange', 'all'));
        $query = ExchangeCalendarEvent::query()->orderBy('event_date')->orderBy('exchange');
        if ($exchange !== 'all') {
            $query->whereIn('exchange', [strtoupper($exchange), ExchangeCalendarEvent::EXCHANGE_BOTH]);
        }
        if ($request->filled('year')) {
            $query->whereYear('event_date', (int) $request->year);
        }
        if ($request->filled('from')) {
            $query->whereDate('event_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('event_date', '<=', $request->to);
        }
        if ($request->filled('type')) {
            $query->where('event_type', $request->type);
        }

        $events = $query->get()->map(fn (ExchangeCalendarEvent $event) => $this->eventPayload($event))->values();

        return response()->json([
            'success' => true,
            'filters' => $validator->validated(),
            'count' => $events->count(),
            'data' => $events,
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date_format:Y-m-d'],
            'exchange' => ['nullable', Rule::in(['nse', 'bse', 'all'])],
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $request->date, config('exchange_calendar.timezone'));
        $requested = strtolower((string) $request->input('exchange', 'all'));
        $exchanges = $requested === 'all' ? ['NSE', 'BSE'] : [strtoupper($requested)];
        $events = ExchangeCalendarEvent::query()
            ->whereDate('event_date', $date->toDateString())
            ->whereIn('exchange', array_merge($exchanges, [ExchangeCalendarEvent::EXCHANGE_BOTH]))
            ->get();
        $coverage = ExchangeCalendarEvent::query()
            ->whereIn('exchange', array_merge($exchanges, [ExchangeCalendarEvent::EXCHANGE_BOTH]))
            ->whereBetween('event_date', [
                $date->startOfYear()->toDateString(),
                $date->endOfYear()->toDateString(),
            ])
            ->pluck('exchange')
            ->flip();
        $isWeekend = $date->isWeekend();

        $results = collect($exchanges)->map(function (string $exchange) use ($events, $coverage, $date, $isWeekend) {
            /** @var ExchangeCalendarEvent|null $event */
            $event = $events->firstWhere('exchange', $exchange)
                ?? $events->firstWhere('exchange', ExchangeCalendarEvent::EXCHANGE_BOTH);
            $calendarAvailable = $coverage->has($exchange)
                || $coverage->has(ExchangeCalendarEvent::EXCHANGE_BOTH);
            $hasSpecialSession = $event?->event_type === ExchangeCalendarEvent::TYPE_MUHURAT;
            $isFullDayHoliday = in_array($event?->event_type, [
                ExchangeCalendarEvent::TYPE_HOLIDAY,
                ExchangeCalendarEvent::TYPE_WEEKEND,
            ], true);
            $isTradingDay = $hasSpecialSession
                ? true
                : (($isWeekend || $isFullDayHoliday) ? false : ($calendarAvailable ? true : null));
            $status = $hasSpecialSession
                ? 'muhurat'
                : (($isWeekend || $isFullDayHoliday) ? 'closed' : ($calendarAvailable ? 'open' : 'unknown'));

            return [
                'exchange' => $exchange,
                'date' => $date->toDateString(),
                'day' => $date->format('l'),
                'calendar_available' => $calendarAvailable,
                'is_weekend' => $isWeekend,
                'is_exchange_holiday' => $event !== null,
                'has_special_session' => $hasSpecialSession,
                'is_trading_day' => $isTradingDay,
                'status' => $status,
                'event' => $event ? $this->eventPayload($event) : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'date' => $date->toDateString(),
            'data' => $requested === 'all' ? $results : $results->first(),
        ]);
    }

    private function eventPayload(ExchangeCalendarEvent $event): array
    {
        return [
            'exchange' => $event->exchange,
            'exchanges' => $event->exchange === ExchangeCalendarEvent::EXCHANGE_BOTH ? ['NSE', 'BSE'] : [$event->exchange],
            'segment' => $event->segment,
            'date' => $event->event_date->toDateString(),
            'name' => $event->name,
            'type' => $event->event_type,
            'session_start' => $event->session_start,
            'session_end' => $event->session_end,
            'source' => $event->source,
            'synced_at' => $event->synced_at?->toIso8601String(),
        ];
    }
}
