<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'admin@geodata.com')->first();
if ($user) {
    echo "User found: " . $user->email . " | is_admin: " . $user->is_admin . " | status: " . $user->status . "\n";
} else {
    echo "User not found\n";
}
