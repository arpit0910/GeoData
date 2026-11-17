<?php

namespace App\Services\TechnologyNews;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

class SafeHttp
{
    public function validateUrl(string $url): array
    {
        $p = parse_url($url);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !is_array($p) || ($p['scheme'] ?? '') !== 'https'
            || isset($p['user']) || isset($p['pass']) || (isset($p['port']) && $p['port'] !== 443)
            || !isset($p['host']) || filter_var($p['host'], FILTER_VALIDATE_IP)
            || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $p['host'])) {
            throw new RuntimeException('Only public HTTPS hostnames on port 443 are allowed.');
        }
        return $p;
    }

    protected function addresses(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        return array_values(array_filter(array_map(fn ($r) => $r['ip'] ?? $r['ipv6'] ?? null, $records ?: [])));
    }

    public function get(string $url, array $headers = [])
    {
        $p = $this->validateUrl($url);
        $addresses = $this->addresses($p['host']);
        if (!$addresses) { throw new RuntimeException('Source DNS lookup failed.'); }
        foreach ($addresses as $ip) {
            if (!str_contains($ip, ':') && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('Private or reserved source address refused.');
            }
        }
        $ipv4 = array_values(array_filter($addresses, fn ($ip) => !str_contains($ip, ':')));
        if (!$ipv4) { throw new RuntimeException('No public IPv4 address available.'); }
        $response = null;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $response = Http::withHeaders(array_merge(['User-Agent' => config('technology_news.user_agent'), 'Accept' => 'application/json, application/rss+xml, application/atom+xml, application/xml', 'Accept-Encoding' => 'identity'], $headers))
                    ->connectTimeout(5)->timeout(config('technology_news.timeout'))
                    ->withOptions([
                        'verify' => config('technology_news.ca_bundle') ?: true,
                        'allow_redirects' => false,
                        'decode_content' => false,
                        'curl' => [CURLOPT_RESOLVE => [$p['host'].':443:'.$ipv4[0]], CURLOPT_PROXY => ''],
                        'on_headers' => function ($r) {
                            if ((int) $r->getHeaderLine('Content-Length') > config('technology_news.max_bytes')) {
                                throw new RuntimeException('Source response too large.');
                            }
                        },
                        'progress' => function ($total, $downloaded) {
                            if ($downloaded > config('technology_news.max_bytes')) { throw new RuntimeException('Source response too large.'); }
                        },
                    ])->get($url);
                if ($response->status() === 429 || $response->status() === 503) {
                    $header = $response->header('Retry-After');
                    $seconds = is_numeric($header) ? max(1, (int) $header) : max(60, (strtotime($header ?: '') ?: time() + 60) - time());
                    throw new RateLimited($seconds);
                }
                if ($response->status() < 500) { break; }
            } catch (ConnectionException $e) {
                if ($attempt === 2) { throw $e; }
            }
            if ($attempt < 2) { usleep(250000 * (2 ** $attempt)); }
        }
        if (!$response || ($response->status() !== 304 && !$response->successful())) {
            throw new RuntimeException('Source HTTP request failed'.($response ? ' ('.$response->status().')' : '').'. Redirects are refused; configure the final feed URL.');
        }
        if ($response->header('Content-Encoding') && strtolower($response->header('Content-Encoding')) !== 'identity') {
            throw new RuntimeException('Compressed source responses are refused to bound parsing memory.');
        }
        if (strlen($response->body()) > config('technology_news.max_bytes')) { throw new RuntimeException('Source response too large.'); }
        return $response;
    }
}
