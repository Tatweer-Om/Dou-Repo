<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saloon_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('saloon_bookings', 'staff_ids')) {
                $table->json('staff_ids')->nullable()->after('staff_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('saloon_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('saloon_bookings', 'staff_ids')) {
                $table->dropColumn('staff_ids');
            }
        });
    }
};
