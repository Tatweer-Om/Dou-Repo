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
        Schema::create('pos_payment_expences', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->string('order_no')->nullable();
            $table->string('total_amount');
            $table->string('accoun_id');
            $table->string('account_tax')->nullable();
            $table->string('account_tax_fee')->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('user_id', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_payment_expences');
    }
};
