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
        Schema::create('pos_orders_details', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->string('order_no')->nullable();
            $table->integer('item_id');
            $table->string('item_barcode');
            $table->integer('item_quantity');
            $table->integer('restore_status')->nullable();
            $table->decimal('item_discount_price',50,3)->nullable();
            $table->decimal('item_price',50,3)->nullable();
            $table->decimal('item_total',50,3)->nullable();
            $table->decimal('item_tax',50,3)->nullable();
            $table->decimal('item_profit',50,3)->nullable();
            $table->string('added_by')->nullable();
            $table->string('user_id', 255)->nullable();
            $table->string('branch_id', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_orders_details');
    }
};
