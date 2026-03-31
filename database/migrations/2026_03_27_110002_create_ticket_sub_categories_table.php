<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ticket_sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('ticket_categories')->onDelete('cascade');
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_sub_categories');
    }
};
