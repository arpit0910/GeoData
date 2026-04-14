<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EquityPrice;

class EquityUpdateMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'equities:update-metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update analytical metrics for existing equity prices';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting metrics update for existing records...');
        
        $total = EquityPrice::count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        EquityPrice::chunk(1000, function ($prices) use ($bar) {
            $updates = [];
            foreach ($prices as $price) {
                $nse_gap = null;
                $nse_range = null;
                $nse_intraday = null;
                $nse_ticket = null;

                if ($price->nse_prev_close > 0) {
                    $nse_gap = (($price->nse_open - $price->nse_prev_close) / $price->nse_prev_close) * 100;
                    $nse_range = (($price->nse_high - $price->nse_low) / $price->nse_prev_close) * 100;
                }
                if ($price->nse_open > 0) {
                    $nse_intraday = (($price->nse_close - $price->nse_open) / $price->nse_open) * 100;
                }
                if ($price->nse_trades > 0) {
                    $nse_ticket = $price->nse_turnover / $price->nse_trades;
                }

                $bse_gap = null;
                $bse_range = null;
                $bse_intraday = null;
                $bse_ticket = null;

                if ($price->bse_prev_close > 0) {
                    $bse_gap = (($price->bse_open - $price->bse_prev_close) / $price->bse_prev_close) * 100;
                    $bse_range = (($price->bse_high - $price->bse_low) / $price->bse_prev_close) * 100;
                }
                if ($price->bse_open > 0) {
                    $bse_intraday = (($price->bse_close - $price->bse_open) / $price->bse_open) * 100;
                }
                if ($price->bse_trades > 0) {
                    $bse_ticket = $price->bse_turnover / $price->bse_trades;
                }

                $updates[] = [
                    'id' => $price->id,
                    'equity_id' => $price->equity_id,
                    'isin' => $price->isin,
                    'traded_date' => $price->traded_date->format('Y-m-d'),
                    'nse_gap_pct' => $nse_gap,
                    'nse_range_pct' => $nse_range,
                    'nse_intraday_chg_pct' => $nse_intraday,
                    'nse_avg_ticket_size' => $nse_ticket,
                    'bse_gap_pct' => $bse_gap,
                    'bse_range_pct' => $bse_range,
                    'bse_intraday_chg_pct' => $bse_intraday,
                    'bse_avg_ticket_size' => $bse_ticket,
                ];
            }

            if (!empty($updates)) {
                EquityPrice::upsert($updates, ['id'], [
                    'nse_gap_pct', 'nse_range_pct', 'nse_intraday_chg_pct', 'nse_avg_ticket_size',
                    'bse_gap_pct', 'bse_range_pct', 'bse_intraday_chg_pct', 'bse_avg_ticket_size'
                ]);
            }
            $bar->advance(count($prices));
        });

        $bar->finish();
        $this->newLine();
        $this->info('Metrics update completed.');

        return Command::SUCCESS;
    }
}
