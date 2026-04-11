<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ApiLoadTest extends Command
{
    /**
     * @var string
     */
    protected $signature = 'api:load-test 
                            {endpoint : The relative URI to test (e.g. /api/v1/countries)}
                            {--c|concurrency=10 : Number of concurrent requests}
                            {--n|requests=100 : Total number of requests}
                            {--token= : Bearer token for authentication}
                            {--url=http://localhost:8000 : Base URL of the application}';

    /**
     * @var string
     */
    protected $description = 'Perform a simple load test on a specific API endpoint using concurrent CURL requests.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $endpoint = $this->argument('endpoint');
        $concurrency = (int) $this->option('concurrency');
        $totalRequests = (int) $this->option('requests');
        $token = $this->option('token');
        $baseUrl = rtrim($this->option('url'), '/');
        
        $fullUrl = $baseUrl . '/' . ltrim($endpoint, '/');

        $this->info("Starting Load Test...");
        $this->info("Target URL: {$fullUrl}");
        $this->info("Concurrency: {$concurrency}");
        $this->info("Total Requests: {$totalRequests}");

        $startTime = microtime(true);
        $results = [];
        $completed = 0;

        while ($completed < $totalRequests) {
            $batchSize = min($concurrency, $totalRequests - $completed);
            $batchResults = $this->runBatch($fullUrl, $batchSize, $token);
            $results = array_merge($results, $batchResults);
            $completed += $batchSize;
            $this->output->write(".");
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        $this->newLine(2);
        $this->report($results, $totalTime, $totalRequests, $concurrency);
    }

    protected function runBatch(string $url, int $size, ?string $token): array
    {
        $mh = curl_multi_init();
        $handles = [];

        for ($i = 0; $i < $size; $i++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $headers = ['Accept: application/json'];
            if ($token) {
                $headers[] = "Authorization: Bearer {$token}";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_multi_add_handle($mh, $ch);
            $handles[] = [
                'handle' => $ch,
                'start' => microtime(true)
            ];
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        $batchResults = [];
        foreach ($handles as $h) {
            $ch = $h['handle'];
            $info = curl_getinfo($ch);
            $batchResults[] = [
                'status' => $info['http_code'],
                'time' => (microtime(true) - $h['start']) * 1000, // ms
            ];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        return $batchResults;
    }

    protected function report(array $results, float $totalElapsedTime, int $totalRequests, int $concurrency)
    {
        $statusCodes = [];
        $times = [];

        foreach ($results as $res) {
            $statusCodes[$res['status']] = ($statusCodes[$res['status']] ?? 0) + 1;
            $times[] = $res['time'];
        }

        sort($times);
        $count = count($times);
        $avg = array_sum($times) / $count;
        $min = $times[0];
        $max = $times[$count - 1];
        $p95 = $times[(int)($count * 0.95)];

        $this->table(['Status Code', 'Count'], array_map(fn($k, $v) => [$k, $v], array_keys($statusCodes), array_values($statusCodes)));

        $this->info("--- Execution Statistics ---");
        $this->line("Total Time: " . round($totalElapsedTime, 2) . "s");
        $this->line("Requests/sec: " . round($totalRequests / $totalElapsedTime, 2));
        $this->line("Average Latency: " . round($avg, 2) . "ms");
        $this->line("Min Latency: " . round($min, 2) . "ms");
        $this->line("Max Latency: " . round($max, 2) . "ms");
        $this->line("95th Percentile: " . round($p95, 2) . "ms");
    }
}
