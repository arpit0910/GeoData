<?php

namespace App\Console\Commands;

use App\Support\TlsCaBundle;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Backfill historical mutual-fund NAVs from AMFI's official bulk feed.
 *
 * The feed is downloaded in small date windows instead of making one request
 * per scheme to a third-party service. Existing ISIN/date rows are updated,
 * which makes interrupted runs safe to restart with the same date range.
 */
class MfBackfillCommand extends Command
{
    protected $signature = 'mf:backfill
                            {--from=2023-04-01 : First NAV date to store (YYYY-MM-DD)}
                            {--to=             : Last NAV date to store; defaults to today IST}
                            {--window-days=7   : Number of calendar days per AMFI request}
                            {--chunk=1000      : Rows per database upsert}
                            {--delay=250000    : Microseconds between AMFI requests}';

    protected $description = 'Backfill historical NAV data from the official AMFI bulk history feed';

    private const AMFI_HISTORY_URL = 'https://portal.amfiindia.com/DownloadNAVHistoryReport_Po.aspx';

    protected bool $shouldStop = false;

    public function handle(): int
    {
        @ini_set('memory_limit', '1024M');
        set_time_limit(0);
        DB::disableQueryLog();
        $this->setupSignals();

        try {
            $from = $this->parseDateOption((string) $this->option('from'));
            $to = $this->option('to')
                ? $this->parseDateOption((string) $this->option('to'))
                : CarbonImmutable::today('Asia/Kolkata');
        } catch (Throwable) {
            $this->error('The --from and --to values must use YYYY-MM-DD format.');

            return Command::INVALID;
        }

        if ($from->gt($to)) {
            $this->error('--from must be earlier than or equal to --to.');

            return Command::INVALID;
        }

        $windowDays = min(31, max(1, (int) $this->option('window-days')));
        $chunkSize = max(1, (int) $this->option('chunk'));
        $delay = max(0, (int) $this->option('delay'));
        $windows = $this->windows($from, $to, $windowDays);
        $funds = DB::table('mutual_funds')
            ->select('id', 'isin', 'isin_reinvest', 'scheme_code')
            ->get();

        if ($funds->isEmpty()) {
            $this->error('No schemes found. Run `sync:mf-daily --force --skip-returns` first.');

            return Command::FAILURE;
        }

        $bySchemeCode = $funds->groupBy(fn ($fund) => (string) $fund->scheme_code);
        $byIsin = [];
        foreach ($funds as $fund) {
            $byIsin[strtoupper((string) $fund->isin)] = $fund;
            if ($fund->isin_reinvest) {
                $byIsin[strtoupper((string) $fund->isin_reinvest)] = $fund;
            }
        }

        $this->info(sprintf(
            '[mf:backfill] AMFI bulk history %s to %s (%d windows, %d-day maximum)',
            $from->toDateString(),
            $to->toDateString(),
            count($windows),
            $windowDays
        ));
        Log::info('[mf:backfill] started', [
            'provider' => 'AMFI',
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'windows' => count($windows),
        ]);

        $received = 0;
        $written = 0;
        $unmatched = 0;
        $failedWindows = [];
        $startedAt = microtime(true);

        foreach ($windows as $index => [$windowFrom, $windowTo]) {
            if ($this->shouldStop) {
                $this->warn('Stop requested; ending after the last completed window.');
                break;
            }

            $label = $windowFrom->toDateString().' to '.$windowTo->toDateString();
            $this->line(sprintf('[%d/%d] Downloading %s ...', $index + 1, count($windows), $label));

            try {
                $response = $this->download($windowFrom, $windowTo);
                [$rows, $windowReceived, $windowUnmatched] = $this->parse(
                    $response->body(),
                    $bySchemeCode,
                    $byIsin
                );
                $windowWritten = $this->upsert($rows, $chunkSize);
                $received += $windowReceived;
                $written += $windowWritten;
                $unmatched += $windowUnmatched;

                $this->info(sprintf(
                    '  completed: received=%s matched=%s unmatched=%s',
                    number_format($windowReceived),
                    number_format(count($rows)),
                    number_format($windowUnmatched)
                ));
                Log::info('[mf:backfill] window complete', [
                    'from' => $windowFrom->toDateString(),
                    'to' => $windowTo->toDateString(),
                    'received' => $windowReceived,
                    'matched' => count($rows),
                    'unmatched' => $windowUnmatched,
                ]);
            } catch (Throwable $exception) {
                $failedWindows[] = $label;
                $this->error("  failed: {$exception->getMessage()}");
                Log::error('[mf:backfill] window failed', [
                    'from' => $windowFrom->toDateString(),
                    'to' => $windowTo->toDateString(),
                    'error' => $exception->getMessage(),
                ]);
            }

            if ($delay > 0 && $index + 1 < count($windows)) {
                usleep($delay);
            }
        }

        $elapsed = round(microtime(true) - $startedAt, 1);
        $this->newLine();
        $this->info(sprintf(
            'Finished in %ss: received=%s, written=%s, unmatched=%s, failed windows=%d.',
            $elapsed,
            number_format($received),
            number_format($written),
            number_format($unmatched),
            count($failedWindows)
        ));

        if ($failedWindows !== []) {
            $this->warn('Rerun the same command safely. Completed rows will be updated without duplicates.');
        }

        Log::info('[mf:backfill] complete', [
            'received' => $received,
            'written' => $written,
            'unmatched' => $unmatched,
            'failed_windows' => $failedWindows,
            'elapsed_s' => $elapsed,
        ]);

        return $failedWindows === [] ? Command::SUCCESS : Command::FAILURE;
    }

    /** @return array<int, array{0: CarbonImmutable, 1: CarbonImmutable}> */
    private function windows(CarbonImmutable $from, CarbonImmutable $to, int $days): array
    {
        $windows = [];
        $cursor = $from;

        while ($cursor->lte($to)) {
            $end = $cursor->addDays($days - 1);
            if ($end->gt($to)) {
                $end = $to;
            }
            $windows[] = [$cursor, $end];
            $cursor = $end->addDay();
        }

        return $windows;
    }

    private function download(CarbonImmutable $from, CarbonImmutable $to): Response
    {
        $verify = TlsCaBundle::resolve(config('market_data.amfi_ca_bundle'));
        $response = Http::retry(4, 1000, null, false)
            ->connectTimeout(15)
            ->timeout(120)
            ->withOptions(['verify' => $verify])
            ->withHeaders(['User-Agent' => 'SetuGeo MF Backfill/2.0'])
            ->get(self::AMFI_HISTORY_URL, [
                'frmdt' => $from->format('d-M-Y'),
                'todt' => $to->format('d-M-Y'),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('AMFI history download returned HTTP '.$response->status().'.');
        }
        if (! str_contains($response->body(), 'Scheme Code;NAV Name;')) {
            throw new RuntimeException('AMFI history download returned an unexpected response.');
        }

        return $response;
    }

    /**
     * @return array{0: array<int, array{isin: string, mf_id: int, nav_date: string, nav: float}>, 1: int, 2: int}
     */
    private function parse(string $body, $bySchemeCode, array $byIsin): array
    {
        $rows = [];
        $received = 0;
        $unmatched = 0;

        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            $fields = array_map('trim', explode(';', trim($line)));
            if (count($fields) < 8 || ! is_numeric($fields[0]) || ! is_numeric($fields[6])) {
                continue;
            }

            $received++;
            $schemeCode = (string) $fields[0];
            $growthIsin = strtoupper($fields[4]);
            $reinvestIsin = strtoupper($fields[5]);
            $fund = $byIsin[$growthIsin] ?? $byIsin[$reinvestIsin] ?? null;

            if (! $fund) {
                $candidates = $bySchemeCode->get($schemeCode, collect());
                $fund = $candidates->first();
            }

            try {
                $parsedDate = CarbonImmutable::createFromFormat('d-M-Y', $fields[7], 'Asia/Kolkata');
                $navDate = $parsedDate && $parsedDate->format('d-M-Y') === $fields[7]
                    ? $parsedDate->toDateString()
                    : null;
            } catch (Throwable) {
                $navDate = null;
            }

            if (! $fund || ! $navDate || (float) $fields[6] <= 0) {
                $unmatched++;
                continue;
            }

            $key = strtoupper((string) $fund->isin).'|'.$navDate;
            $rows[$key] = [
                'isin' => (string) $fund->isin,
                'mf_id' => (int) $fund->id,
                'nav_date' => $navDate,
                'nav' => round((float) $fields[6], 4),
            ];
        }

        return [array_values($rows), $received, $unmatched];
    }

    private function upsert(array $rows, int $chunkSize): int
    {
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table('mutual_fund_prices')->upsert(
                $chunk,
                ['isin', 'nav_date'],
                ['mf_id', 'nav']
            );
        }

        return count($rows);
    }

    private function setupSignals(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);
        $handler = function (): void {
            $this->shouldStop = true;
        };
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }

    private function parseDateOption(string $value): CarbonImmutable
    {
        $date = CarbonImmutable::createFromFormat('Y-m-d', $value, 'Asia/Kolkata');

        if (! $date || $date->format('Y-m-d') !== $value) {
            throw new RuntimeException('Invalid date.');
        }

        return $date->startOfDay();
    }
}
