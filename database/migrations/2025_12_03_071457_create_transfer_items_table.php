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
        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_id');
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->string('abaya_code');
            $table->string('item_type')->nullable(); // size, color, color_size
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->string('color_name')->nullable();
            $table->string('size_name')->nullable();
            $table->integer('quantity');
            $table->string('from_location'); // main, channel-1, boutique-2
            $table->string('to_location'); // main, channel-1, boutique-2
            $table->string('added_by')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
            
            $table->foreign('transfer_id')->references('id')->on('transfers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_items');
    }
};
