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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('paid_by')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('reciept_no')->nullable();
            $table->string('expense_name');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('payment_method')->nullable(); // account_id
            $table->decimal('amount', 10, 3);
            $table->date('expense_date');
            $table->longText('notes')->nullable();
            $table->string('expense_image')->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('expense_categories')->onDelete('set null');
            $table->foreign('payment_method')->references('id')->on('accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

