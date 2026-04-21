<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_tools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 15, 3)->default(0);
            $table->text('notes')->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_tools');
    }
};
