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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_branch')->nullable();
            $table->string('account_no')->nullable();
            $table->decimal('opening_balance', 50, 3)->nullable();
            $table->decimal('commission', 50, 2)->nullable();
            $table->integer('account_type')->nullable()->comment('1 : Normal Account 2 : Saving Account');
            $table->text('notes')->nullable();
            $table->integer('account_status')->nullable();
            $table->integer('branch_id')->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};

