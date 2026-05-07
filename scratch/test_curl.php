<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.mfapi.in/mf");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

echo "Starting curl request...\n";
$start = microtime(true);
$output = curl_exec($ch);
$end = microtime(true);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch) . "\n";
    echo 'Time taken: ' . ($end - $start) . "s\n";
} else {
    echo "Success!\n";
    echo 'Time taken: ' . ($end - $start) . "s\n";
    echo "Content length: " . strlen($output) . "\n";
}
curl_close($ch);
