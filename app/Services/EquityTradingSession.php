<?php

namespace App\Services;

use App\Models\ExchangeCalendarEvent;
use Carbon\CarbonInterface;

class EquityTradingSession
{
    public function isOpen(CarbonInterface $now, string $regularStart = '09:15', string $regularEnd = '15:30'): bool
    {
        $local = $now->copy()->setTimezone('Asia/Kolkata');
        $time = $local->format('H:i');
        $events = ExchangeCalendarEvent::query()
            ->where('segment', 'equity')
            ->where('event_date', $local->toDateString())
            ->whereIn('exchange', ['NSE', 'BSE', ExchangeCalendarEvent::EXCHANGE_BOTH])
            ->get()->keyBy('exchange');

        foreach (['NSE', 'BSE'] as $exchange) {
            $event = $events->get($exchange) ?? $events->get(ExchangeCalendarEvent::EXCHANGE_BOTH);
            if ($event?->hasSpecialSession()) {
                if ($event->session_start && $event->session_end
                    && $time >= substr($event->session_start, 0, 5)
                    && $time <= substr($event->session_end, 0, 5)) {
                    return true;
                }
            } elseif ($event === null && !$local->isWeekend() && $time >= $regularStart && $time <= $regularEnd) {
                return true;
            }
        }

        return false;
    }
}
