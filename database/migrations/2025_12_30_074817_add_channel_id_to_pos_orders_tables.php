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
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('channel_id')->nullable()->after('user_id');
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('set null');
        });

        Schema::table('pos_orders_details', function (Blueprint $table) {
            $table->unsignedBigInteger('channel_id')->nullable()->after('branch_id');
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_orders_details', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropColumn('channel_id');
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropColumn('channel_id');
        });
    }
};
