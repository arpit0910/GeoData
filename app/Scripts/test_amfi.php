<?php
$html = file_get_contents('https://www.amfiindia.com/research-information/other-data/categorization-of-stocks');
echo strlen($html) > 0 ? "SUCCESS\n" : "FAIL\n";
