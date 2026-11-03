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
                if (! $force && $amc->logo_path && str_starts_with($amc->logo_path, 'assets/amc-favicons/') && File::isFile(public_path($amc->logo_path))
                    && ($amc->logo_source_url === $source || ! str_starts_with((string) $amc->logo_source_url, 'https://www.amfiindia.com/uploads/'))) {
                    $stats['existing']++;
                    continue;
                }
                $body = $this->request()->get($source)->throw()->body();
                // AMFI's square icon is already favicon-sized. Where its
                // "icon" is actually a wide wordmark, use the AMC site's
                // real favicon instead of saving a full corporate logo.
                if ($this->isWideImage($body)) {
                    [$source, $body] = $this->websiteFavicon($member);
                }
                // CorelDRAW/Illustrator exports include an external SVG DTD.
                // Remove that declaration; never load it or allow entities.
                $body = preg_replace('/<!DOCTYPE\s+svg\s+(?:PUBLIC\s+"[^"]*"\s+"[^"]*"|SYSTEM\s+"[^"]*")\s*>/i', '', $body);
                $extension = $this->imageExtension($body);
                $path = 'assets/amc-favicons/'.$amc->amfi_id.'-'.substr(hash('sha256', $body), 0, 16).'.'.$extension;
                File::ensureDirectoryExists(public_path('assets/amc-favicons'));
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
        if (strlen($body) >= 22 && substr($body, 0, 4) === "\x00\x00\x01\x00") {
            return 'ico';
        }
        $mime = (@getimagesizefromstring($body))['mime'] ?? null;
        return match ($mime) {
            'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif',
            default => throw new RuntimeException('AMFI did not return a supported image.'),
        };
    }

    private function isWideImage(string $body): bool
    {
        if (preg_match('/viewBox=["\']\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)\s*["\']/i', $body, $box)) {
            return (float) $box[4] > 0 && (float) $box[3] / (float) $box[4] > 1.5;
        }
        $image = @getimagesizefromstring($body);
        return $image && $image[1] > 0 && $image[0] / $image[1] > 1.5;
    }

    private function websiteFavicon(array $member): array
    {
        $website = trim((string) ($member['amc_website'] ?? ''));
        if ($website !== '' && ! preg_match('~^https?://~i', $website)) {
            $website = 'https://'.$website;
        }
        $host = parse_url($website, PHP_URL_HOST);
        if (! $host || ! str_contains($host, '.') || ! in_array(parse_url($website, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new RuntimeException('No official website is available for favicon discovery.');
        }
        $response = $this->request()->get($website)->throw();
        $website = (string) ($response->effectiveUri() ?: $website);
        $html = $response->body();
        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NONET);
        $base = $dom->getElementsByTagName('base')->item(0)?->getAttribute('href');
        if ($base) {
            $website = (string) \GuzzleHttp\Psr7\UriResolver::resolve(new \GuzzleHttp\Psr7\Uri($website), new \GuzzleHttp\Psr7\Uri($base));
        }
        $candidates = [];
        foreach ($dom->getElementsByTagName('link') as $link) {
            if (preg_match('/(?:^|\s)(?:icon|apple-touch-icon)(?:\s|$)/i', $link->getAttribute('rel'))) {
                $candidates[] = (string) \GuzzleHttp\Psr7\UriResolver::resolve(new \GuzzleHttp\Psr7\Uri($website), new \GuzzleHttp\Psr7\Uri($link->getAttribute('href')));
            }
        }
        $candidates[] = parse_url($website, PHP_URL_SCHEME).'://'.$host.'/favicon.ico';
        foreach (array_unique($candidates) as $url) {
            if (! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                continue;
            }
            try {
                $body = $this->request()->get($url)->throw()->body();
                $this->imageExtension(preg_replace('/<!DOCTYPE[^>]*>/i', '', $body));
                if (! $this->isWideImage($body)) {
                    return [$url, $body];
                }
            } catch (Throwable) {
                // Try the next published favicon; never substitute a wordmark.
            }
        }
        throw new RuntimeException('The official AMC website did not provide a usable favicon.');
    }
}
