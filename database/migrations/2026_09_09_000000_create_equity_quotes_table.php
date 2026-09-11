<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('equity_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('isin', 12);
            $table->string('exchange', 8);
            $table->string('symbol');
            $table->decimal('price', 20, 4);
            $table->timestamp('quoted_at');
            $table->timestamp('fetched_at');
            $table->json('payload');
            $table->unique(['isin', 'exchange']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('equity_quotes');
    }
};
