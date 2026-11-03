<?php

namespace App\Services;

use App\Models\Amc;
use App\Support\TlsCaBundle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class AmcSyncService
{
    public function sync(bool $force = false): array
    {
        $html = $this->request()->get('https://www.amfiindia.com/member/53')->throw()->body();
        $members = $this->members($html);
        if ($members === []) {
            throw new RuntimeException('AMFI did not return an AMC directory. Existing details and icons were preserved.');
        }
        $stats = ['saved' => 0, 'downloaded' => 0, 'existing' => 0, 'errors' => []];
        foreach ($members as $member) {
            if (empty($member['mf_id']) || empty($member['mf_name'])) {
                continue;
            }
            $amc = Amc::updateOrCreate(['amfi_id' => (int) $member['mf_id']], [
                'name' => trim($member['mf_name']),
                'company_name' => $member['amc_name'] ?? null,
                'website' => $member['amc_website'] ?? null,
                'details' => $member,
                'synced_at' => now(),
            ]);
            $stats['saved']++;
            try {
                $override = config('market_data.amc_icons.sources.'.$amc->amfi_id);
                $source = data_get($member, 'icons.0.url') ?: data_get($member, 'icon_wordmark.url') ?: $override;
                if (! is_string($source) || $source === '') {
                    throw new RuntimeException('AMFI has no icon for this AMC.');
                }
                $source = str_starts_with($source, '/uploads/') ? 'https://www.amfiindia.com'.$source : $source;
                if (! str_starts_with($source, 'https://www.amfiindia.com/uploads/') && $source !== $override) {
                    throw new RuntimeException('Unexpected AMC icon source.');
                }
                if (! $force && $amc->logo_source_url === $source && $amc->logo_path && File::isFile(public_path($amc->logo_path))) {
                    $stats['existing']++;
                    continue;
                }
                $body = $this->request()->get($source)->throw()->body();
                // CorelDRAW/Illustrator exports include an external SVG DTD.
                // Remove that declaration; never load it or allow entities.
                $body = preg_replace('/<!DOCTYPE\s+svg\s+(?:PUBLIC\s+"[^"]*"\s+"[^"]*"|SYSTEM\s+"[^"]*")\s*>/i', '', $body);
                $extension = $this->imageExtension($body);
                $path = 'assets/amc-icons/'.$amc->amfi_id.'-'.substr(hash('sha256', $body), 0, 16).'.'.$extension;
                File::ensureDirectoryExists(public_path('assets/amc-icons'));
                File::put(public_path($path), $body);
                $amc->update(['logo_path' => $path, 'logo_source_url' => $source, 'icon_synced_at' => now()]);
                $stats['downloaded']++;
            } catch (Throwable $exception) {
                $stats['errors'][] = $amc->name.': '.$exception->getMessage();
            }
        }
        return $stats;
    }

    private function request()
    {
        return Http::connectTimeout(10)->timeout(30)
            ->withOptions(['verify' => TlsCaBundle::resolve(config('market_data.amfi_ca_bundle'))]);
    }

    public function members(string $html): array
    {
        // AMFI embeds the complete directory in its server-rendered Next.js
        // payload. Decode JSON rather than evaluating any website JavaScript.
        preg_match_all('/self\.__next_f\.push\((.*?)\)<\/script>/s', $html, $matches);
        foreach ($matches[1] as $json) {
            $chunk = json_decode($json, true);
            if (! is_string($chunk[1] ?? null)) {
                continue;
            }
            foreach (explode("\n", $chunk[1]) as $line) {
                $colon = strpos($line, ':');
                if ($colon === false) {
                    continue;
                }
                $found = $this->findMembers(json_decode(substr($line, $colon + 1), true));
                if ($found !== []) {
                    return $found;
                }
            }
        }
        return [];
    }

    private function findMembers(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }
        if (isset($value['members']) && is_array($value['members'])) {
            return $value['members'];
        }
        foreach ($value as $child) {
            if (($found = $this->findMembers($child)) !== []) {
                return $found;
            }
        }
        return [];
    }

    private function imageExtension(string $body): string
    {
        if ($body === '' || strlen($body) > 2097152) {
            throw new RuntimeException('AMC icon is empty or exceeds 2 MB.');
        }
        if (stripos($body, '<svg') !== false) {
            if (preg_match('/<(?:script|foreignObject|!DOCTYPE|!ENTITY)\b|\bon\w+\s*=|javascript:/i', $body)) {
                throw new RuntimeException('AMC icon contains unsafe SVG content.');
            }
            return 'svg';
        }
        $mime = (@getimagesizefromstring($body))['mime'] ?? null;
        return match ($mime) {
            'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif',
            default => throw new RuntimeException('AMFI did not return a supported image.'),
        };
    }
}
