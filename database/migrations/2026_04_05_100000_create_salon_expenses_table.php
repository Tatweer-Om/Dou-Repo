<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_expense_category_id')->nullable()->constrained('salon_expense_categories')->nullOnDelete();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('reciept_no')->nullable();
            $table->string('expense_name');
            $table->unsignedBigInteger('payment_method')->nullable()->comment('accounts.id');
            $table->decimal('amount', 15, 3)->default(0);
            $table->date('expense_date');
            $table->text('notes')->nullable();
            $table->string('expense_image')->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_expenses');
    }
};
