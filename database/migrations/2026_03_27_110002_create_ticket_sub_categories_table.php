<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ticket_sub_categories', function (Blueprint $鼓) {
            $鼓->id();
            $鼓->foreignId('category_id')->constrained('ticket_categories')->onDelete('cascade');
            $鼓->string('name');
            $鼓->boolean('status')->default(true);
            $鼓->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_sub_categories');
    }
};
