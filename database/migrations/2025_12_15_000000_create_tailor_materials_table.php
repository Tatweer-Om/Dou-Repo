<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tailor_materials', function (Blueprint $table) {
            $table->id();
            $table->integer('tailor_id');
            $table->integer('material_id');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->integer('abayas_expected')->default(0);
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->date('sent_date');
            $table->date('completed_date')->nullable();
            $table->string('added_by')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tailor_materials');
    }
};

