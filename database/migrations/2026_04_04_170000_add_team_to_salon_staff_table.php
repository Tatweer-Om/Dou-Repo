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
        Schema::table('salon_staff', function (Blueprint $table) {
            if (!Schema::hasColumn('salon_staff', 'team')) {
                $table->integer('team')->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salon_staff', function (Blueprint $table) {
            if (Schema::hasColumn('salon_staff', 'team')) {
                $table->dropColumn('team');
            }
        });
    }
};
