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
        Schema::table('special_order_items', function (Blueprint $table) {
            $table->string('sending_summary_number', 100)->nullable()->after('tailor_order_no');
            $table->string('list_number', 50)->nullable()->after('sending_summary_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_order_items', function (Blueprint $table) {
            $table->dropColumn(['sending_summary_number', 'list_number']);
        });
    }
};
