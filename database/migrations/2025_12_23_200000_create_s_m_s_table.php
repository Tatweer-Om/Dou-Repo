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
        Schema::create('s_m_s', function (Blueprint $table) {
            $table->id();
            $table->text('sms'); // base64 encoded
            $table->integer('sms_status'); // 1, 2, 3, 4, 5
            $table->string('message_type')->nullable(); // Pos Order, Special Order, Repairing (customer), etc.
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('s_m_s');
    }
};

