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
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
             $table->string('customer_id')->nullable();
            $table->string('order_type')->nullable();
            $table->integer('item_count');
            $table->decimal('paid_amount',50,3);
            $table->decimal('total_amount',50,3);
            $table->string('discount_type')->nullable();
            $table->decimal('total_discount',50,3)->nullable();
            $table->integer('profit')->nullable();
            $table->integer('return_status')->default(0)->nullable();
            $table->integer('restore_status')->default(0)->nullable();
            $table->string('order_no')->nullable();
            $table->longText('notes')->nullable()->nullable();
            $table->string('added_by')->nullable();
            $table->string('user_id', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_orders');
    }
};
