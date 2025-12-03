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
        Schema::create('transfer_item_histories', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_id');
            $table->string('item_code')->nullable();
            $table->string('item_size')->nullable();
            $table->string('item_color')->nullable();
            $table->string('item_previous_quantity')->nullable();
            $table->string('quantity_action')->nullable();
            $table->string('item_new_quantity')->nullable();
    $table->integer('quantity_pulled')->default(0);
    $table->integer('quantity_pushed')->default(0);
            $table->string('added_by')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_item_histories');
    }
};
