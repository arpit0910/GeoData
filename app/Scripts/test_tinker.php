<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$e = App\Models\Equity::where('nse_symbol', 'RELIANCE')->first();
$e->series = 'EQ';
$e->save();
var_dump($e->series);
$e2 = App\Models\Equity::find($e->id);
var_dump($e2->series);
