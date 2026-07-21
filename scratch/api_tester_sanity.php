<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = app(App\Http\Controllers\Admin\ApiTesterController::class);
$ref = new ReflectionClass($controller);
$method = $ref->getMethod('sampleData');
$method->setAccessible(true);
$data = $method->invoke($controller);
echo isset($data['index_code']) ? 'sampleData ok' : 'sampleData missing';
