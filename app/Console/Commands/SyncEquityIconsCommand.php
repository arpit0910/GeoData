<?php

namespace App\Console\Commands;

use App\Models\Equity;
use App\Support\TlsCaBundle;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

class SyncEquityIconsCommand extends Command
{
    protected $signature = 'equities:sync-icons
        {--force : Download icons that already exist again}
        {--limit= : Limit the number of equities processed}';

    protected $description = 'Download company icons for active stocks from the configured ISIN logo source';

    public function handle(): int
    {
        $urlTemplate = trim((string) config('market_data.equity_icons.url'));
        if ($urlTemplate === '' || ! str_contains($urlTemplate, '{isin}')) {
            $this->error('The equity icon URL must contain an {isin} placeholder.');
            return self::FAILURE;
        }

        $directory = public_path('assets/equity-icons');
        File::ensureDirectoryExists($directory);
        $limit = $this->option('limit') === null ? null : max(1, (int) $this->option('limit'));
        $query = Equity::query()
            ->where('is_active', true)
            ->whereIn('series', ['EQ', 'BE', 'SM', 'BZ'])
            ->whereNotNull('isin')
            ->where('isin', '<>', '')
            ->orderBy('id');
        if ($limit !== null) {
            $query->limit($limit);
        }

        $equities = $query->get(['id', 'isin', 'company_name', 'logo_url']);
        if ($equities->isEmpty()) {
            $this->warn('No active stocks were found.');
            return self::SUCCESS;
        }

        set_time_limit(0);
        $downloaded = 0;
        $existing = 0;
        $missing = 0;
        $connectionSkipped = 0;
        $failed = 0;
        $progress = $this->output->createProgressBar($equities->count());
        $progress->start();

        foreach ($equities as $equity) {
            $isin = strtoupper(trim((string) $equity->isin));
            $relativePath = 'assets/equity-icons/'.$isin.'.svg';
            $logoUrl = asset($relativePath);
            $path = public_path($relativePath);
            if (! $this->option('force') && File::exists($path)) {
                if ($equity->logo_url !== $logoUrl) {
                    $equity->update(['logo_url' => $logoUrl]);
                }
                $existing++;
                $progress->advance();
                continue;
            }

            try {
                $response = Http::accept('image/svg+xml')
                    ->connectTimeout(10)
                    ->timeout(30)
                    ->retry(2, 250)
                    ->withOptions(['verify' => TlsCaBundle::resolve(config('market_data.ca_bundle'))])
                    ->get(str_replace('{isin}', rawurlencode($isin), $urlTemplate));

                if (! $response->successful()) {
                    throw new \RuntimeException("HTTP {$response->status()}");
                }
                if (trim($response->header('X-Logo-Fallback')) !== '') {
                    $equity->update(['logo_url' => null]);
                    $missing++;
                    $progress->advance();
                    continue;
                }

                $svg = $response->body();
                if (! $this->isSafeSvg($svg)) {
                    throw new \RuntimeException('invalid or unsafe SVG');
                }

                File::put($path, $svg);
                $equity->update(['logo_url' => $logoUrl]);
                $downloaded++;
            } catch (ConnectionException) {
                $connectionSkipped++;
                $this->newLine();
                $this->warn("{$isin} ({$equity->company_name}): connection unavailable; skipped.");
            } catch (Throwable $exception) {
                $failed++;
                $this->newLine();
                $this->warn("{$isin} ({$equity->company_name}): {$exception->getMessage()}");
            }

            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);
        $this->info("Equity icon sync complete: {$downloaded} downloaded, {$existing} already present, {$missing} unavailable, {$connectionSkipped} connection skipped, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isSafeSvg(string $svg): bool
    {
        return $svg !== ''
            && strlen($svg) <= 524288
            && stripos($svg, '<svg') !== false
            && preg_match('/<(?:script|foreignObject)\b|\bon\w+\s*=|javascript:/i', $svg) !== 1;
    }
}
