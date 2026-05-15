<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$u = App\Models\User::where('email', 'admin@setugeo.com')->first();
echo $u ? "Found is_admin=" . $u->is_admin . " role=" . ($u->role ?? 'null') : "Not found";
echo "\n";
