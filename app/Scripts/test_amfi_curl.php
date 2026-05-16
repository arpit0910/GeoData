<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://www.amfiindia.com/research-information/other-data/Categorization-of-Stocks");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // important
$html = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Status: " . $info['http_code'] . "\n";
echo "Length: " . strlen($html) . "\n";

if (preg_match_all('/([a-zA-Z0-9_\-\.\/:]+Categorization[a-zA-Z0-9_\-\.]+\.xlsx)/i', $html, $matches)) {
    print_r(array_unique($matches[1]));
} else {
    echo "No xlsx found\n";
}
