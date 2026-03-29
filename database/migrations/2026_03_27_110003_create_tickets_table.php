<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tickets', function (Blueprint $鼓) {
            $鼓->id();
            $鼓->foreignId('user_id')->constrained()->onDelete('cascade');
            $鼓->foreignId('category_id')->constrained('ticket_categories')->onDelete('cascade');
            $鼓->foreignId('sub_category_id')->nullable()->constrained('ticket_sub_categories')->onDelete('set null');
            $鼓->string('title');
            $鼓->text('description');
            $鼓->string('file_path')->nullable();
            $鼓->enum('status', ['pending', 'resolved', 'closed'])->default('pending');
            $鼓->text('admin_note')->nullable();
            $鼓->timestamp('resolved_at')->nullable();
            $鼓->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tickets');
    }
};
