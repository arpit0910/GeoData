<?php
$zipUrl = "https://www.bseindia.com/download/BhavCopy/Equity/EQ030423_CSV.ZIP";
$tempZip = 'temp_bse_eq.zip';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $zipUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$data = curl_exec($ch);
curl_close($ch);

file_put_contents($tempZip, $data);

$zip = new ZipArchive;
if ($zip->open($tempZip) === TRUE) {
    echo "Files in ZIP:\n";
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        echo "  $name\n";
    }
    $zip->close();
} else {
    echo "Failed to open zip\n";
}
unlink($tempZip);
