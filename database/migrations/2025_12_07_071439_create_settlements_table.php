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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_code')->nullable();
            $table->string('boutique_id')->nullable();
            $table->string('boutique_name')->nullable();
            $table->string('month')->nullable(); // Format: YYYY-MM
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->integer('number_of_items')->default(0);
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->integer('total_difference')->default(0);
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->text('notes')->nullable();
            $table->text('items_data')->nullable(); // JSON data of settlement items
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
        Schema::dropIfExists('settlements');
    }
};
