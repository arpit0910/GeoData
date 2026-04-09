<?php

use App\Models\User;
use App\Models\Timezone;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(User::all() as $user) {
    if ($user->country_id) {
        $tz = Timezone::where('country_id', $user->country_id)->first();
        if ($tz) {
            $user->timezone = $tz->zone_name;
            $user->save();
            echo "Updated user {$user->id} to {$user->timezone}\n";
        }
    }
}
echo "Done.\n";
