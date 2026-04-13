<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

Schema::table('equities', function($table) {
    if (!Schema::hasColumn('equities', 'market_cap_category')) {
        $table->string('market_cap_category')->nullable()->index();
    }
    if (!Schema::hasColumn('equities', 'listing_date')) {
        $table->date('listing_date')->nullable();
    }
});
echo "Columns checked/added successfully.\n";
