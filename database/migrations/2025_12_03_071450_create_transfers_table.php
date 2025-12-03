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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_code');
            $table->string('transfer_type'); //add minus
            $table->string('channel_type'); //boutique pos
            $table->date('date');
            $table->string('quantity');
            $table->string('from');
            $table->string('to');
            $table->string('stock_id')->nullable();
            $table->string('boutique_id')->nullable();
            $table->string('channel_id')->nullable();
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('transfers');
    }
};
