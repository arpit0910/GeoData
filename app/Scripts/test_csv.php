<?php
$c = stream_context_create(['http'=>['header'=>"User-Agent: Mozilla/5.0\r\n"]]);
$csv = file_get_contents('https://nsearchives.nseindia.com/content/equities/EQUITY_L.csv', false, $c);
$lines = explode("\n", str_replace("\r", "", $csv));
foreach($lines as $line) {
    if(strpos($line, 'INE002A01018') !== false) {
        echo $line;
    }
}
