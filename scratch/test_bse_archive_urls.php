<?php
$urls = [
    "https://www.bseindia.com/download/indexbhavcopy/indexbhavcopy_030423.zip",
    "https://www.bseindia.com/download/BhavCopy/Equity/EQ030423_CSV.ZIP",
    "https://www.bseindia.com/download/BhavCopy/Index/indexbhavcopy_030423.zip",
    "https://www.bseindia.com/downloads/indexbhavcopy/indexbhavcopy_030423.zip"
];

foreach ($urls as $url) {
    echo "Testing $url\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "  Status: $status\n";
}
