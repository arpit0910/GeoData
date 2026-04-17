<?php
$ticker = '^BSESN';
$start = 1680480000; // 2023-04-03
$end = 1680566400;
$url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?period1={$start}&period2={$end}&interval=1d";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$data = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $status\n";
echo "Body: " . substr($data, 0, 500) . "...\n";
