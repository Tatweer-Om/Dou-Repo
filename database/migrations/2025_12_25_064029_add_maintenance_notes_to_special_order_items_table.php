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
            $table->longText('maintenance_notes')->nullable()->after('maintenance_cost_bearer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_order_items', function (Blueprint $table) {
            $table->dropColumn('maintenance_notes');
        });
    }
};
