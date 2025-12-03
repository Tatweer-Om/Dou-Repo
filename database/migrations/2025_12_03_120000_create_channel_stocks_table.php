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
        Schema::create('channel_stocks', function (Blueprint $table) {
            $table->id();
            $table->integer('stock_id');
            $table->string('location_type'); // 'main', 'channel', 'boutique'
            $table->integer('location_id')->nullable(); // channel_id or boutique_id, null for main
            $table->string('abaya_code');
            $table->string('item_type')->nullable(); // size, color, color_size
            $table->integer('color_id')->nullable();
            $table->integer('size_id')->nullable();
            $table->string('color_name')->nullable();
            $table->string('size_name')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();
            
      
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_stocks');
    }
};

