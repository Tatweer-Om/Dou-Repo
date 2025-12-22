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
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->string('account_name')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('account_no')->nullable();
            $table->decimal('previous_balance', 10, 3)->default(0);
            $table->decimal('new_total_amount', 10, 3)->default(0);
            $table->string('source')->nullable();
            $table->decimal('expense_amount', 10, 3)->nullable();
            $table->string('expense_name')->nullable();
            $table->date('expense_date')->nullable();
            $table->string('expense_added_by')->nullable();
            $table->string('expense_image')->nullable();
            $table->longText('notes')->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balances');
    }
};

