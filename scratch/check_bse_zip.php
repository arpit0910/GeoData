<?php
$dateStr = '030423';
$url = "https://www.bseindia.com/download/BhavCopy/Equity/EQ{$dateStr}_CSV.ZIP";
echo "Fetching: $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$data = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status == 200) {
    $tempZip = 'temp_bse.zip';
    file_put_contents($tempZip, $data);
    
    $zip = new ZipArchive;
    if ($zip->open($tempZip) === TRUE) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            echo "File in zip: " . $zip->getNameIndex($i) . "\n";
        }
        $zip->close();
    } else {
        echo "Failed to open zip\n";
    }
    unlink($tempZip);
} else {
    echo "Failed to fetch: $status\n";
}
