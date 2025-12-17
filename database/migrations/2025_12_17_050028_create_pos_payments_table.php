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
        Schema::create('pos_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->string('order_no')->nullable();
            $table->decimal('paid_amount',50,3)->default(0);
            $table->decimal('total_amount',50,3)->default(0);
            $table->decimal('discount',50,3)->default(0);
            $table->string('account_id');
            $table->string('notes')->nullable();
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
        Schema::dropIfExists('pos_payments');
    }
};
