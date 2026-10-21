<?php

namespace App\Services;

use App\Models\Ipo;
use App\Models\MarketNudge;
use Illuminate\Support\Facades\DB;

class MarketNudgeService
{
    public function generateOpenIpoNudges(): int
    {
        $created = 0;

        Ipo::query()->where('status', 'open')->orderBy('id')->chunkById(200, function ($ipos) use (&$created) {
            foreach ($ipos as $ipo) {
                $eventAt = $ipo->bidding_start_date ?? $ipo->synced_at ?? now();
                $metrics = [
                    'slug' => $ipo->slug,
                    'symbol' => $ipo->symbol,
                    'issue_type' => $ipo->issue_type,
                    'issue_size' => $ipo->issue_size,
                    'minimum_price' => $ipo->minimum_price,
                    'maximum_price' => $ipo->maximum_price,
                    'bidding_start_date' => $ipo->bidding_start_date?->toDateString(),
                    'bidding_end_date' => $ipo->bidding_end_date?->toDateString(),
                    'total_subscription' => $ipo->total_subscription,
                ];
                $priceBand = $ipo->minimum_price !== null && $ipo->maximum_price !== null
                    ? sprintf(' The price band is ₹%s–₹%s.', number_format($ipo->minimum_price, 2), number_format($ipo->maximum_price, 2))
                    : '';
                $dateRange = $ipo->bidding_start_date && $ipo->bidding_end_date
                    ? sprintf(' Bidding runs from %s to %s.', $ipo->bidding_start_date->format('j M Y'), $ipo->bidding_end_date->format('j M Y'))
                    : '';

                $created += MarketNudge::query()->insertOrIgnore([
                    'source' => 'ipo',
                    'source_key' => $ipo->slug,
                    'rule' => 'ipo_open',
                    'severity' => 'notable',
                    'title' => sprintf('%s is open for subscription', $ipo->name),
                    'message' => sprintf('%s is currently open for subscription.%s%s', $ipo->name, $priceBand, $dateRange),
                    'metrics' => json_encode($metrics, JSON_THROW_ON_ERROR),
                    'event_at' => $eventAt->utc(),
                    'fingerprint' => hash('sha256', 'ipo_open|'.$ipo->slug),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return $created;
    }

    public function generateInstitutionalNudges(): int
    {
        $created = 0;
        DB::table('institutional_activities')
            ->whereIn('investor_type', ['fii', 'dii'])
            ->where('interval', '1D')
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('investor_type', 'fii')
                        ->whereIn('data_type', [
                            'NSE_FO|INDEX_FUTURES',
                            'NSE_FO|STOCK_FUTURES',
                            'NSE_FO|INDEX_OPTIONS',
                            'NSE_FO|STOCK_OPTIONS',
                            'NSE_EQ|CASH',
                        ]);
                })->orWhere(function ($query) {
                    $query->where('investor_type', 'dii')
                        ->where('data_type', 'NSE_EQ|CASH');
                });
            })
            ->whereNotNull('buy_amount')
            ->whereNotNull('sell_amount')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$created) {
                foreach ($rows as $row) {
                    $net = (float) $row->buy_amount - (float) $row->sell_amount;
                    if (abs($net) < 0.0001) {
                        continue;
                    }

                    $type = strtolower($row->investor_type);
                    $label = strtoupper($type);
                    $segment = match ($row->data_type) {
                        'NSE_FO|INDEX_FUTURES' => 'index futures',
                        'NSE_FO|STOCK_FUTURES' => 'stock futures',
                        'NSE_FO|INDEX_OPTIONS' => 'index options',
                        'NSE_FO|STOCK_OPTIONS' => 'stock options',
                        default => 'cash market',
                    };
                    $direction = $net > 0 ? 'buying' : 'selling';
                    $rule = $type.'_net_'.($net > 0 ? 'buy' : 'sell');
                    $eventAt = \Illuminate\Support\Carbon::parse($row->activity_at, 'UTC');
                    $metrics = [
                        'investor_type' => $label,
                        'data_type' => $row->data_type,
                        'interval' => $row->interval,
                        'buy_amount' => (float) $row->buy_amount,
                        'sell_amount' => (float) $row->sell_amount,
                        'net_amount' => round($net, 4),
                    ];

                    $created += MarketNudge::query()->insertOrIgnore([
                        'source' => $type,
                        'source_key' => $row->data_type,
                        'rule' => $rule,
                        'severity' => 'notable',
                        'title' => sprintf('%s %s net %s: %s', $label, $segment, $direction, number_format(abs($net), 2)),
                        'message' => sprintf('%s bought %s and sold %s in %s, for net %s of %s.', $label, number_format((float) $row->buy_amount, 2), number_format((float) $row->sell_amount, 2), $segment, $direction, number_format(abs($net), 2)),
                        'metrics' => json_encode($metrics, JSON_THROW_ON_ERROR),
                        'event_at' => $eventAt,
                        'fingerprint' => hash('sha256', implode('|', [$type, $row->data_type, $eventAt->toDateString()])),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        return $created;
    }

    public function generateAll(): int
    {
        return $this->generateOpenIpoNudges() + $this->generateInstitutionalNudges();
    }
}
