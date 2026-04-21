<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saloon_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('saloon_bookings', 'booking_time_from')) {
                $table->time('booking_time_from')->nullable()->after('booking_time');
            }
            if (!Schema::hasColumn('saloon_bookings', 'booking_time_to')) {
                $table->time('booking_time_to')->nullable()->after('booking_time_from');
            }
        });

        if (Schema::hasColumn('saloon_bookings', 'booking_time_from') && Schema::hasColumn('saloon_bookings', 'booking_time')) {
            DB::statement('UPDATE saloon_bookings SET booking_time_from = booking_time WHERE booking_time IS NOT NULL AND booking_time_from IS NULL');
        }
    }

    public function down(): void
    {
        Schema::table('saloon_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('saloon_bookings', 'booking_time_to')) {
                $table->dropColumn('booking_time_to');
            }
            if (Schema::hasColumn('saloon_bookings', 'booking_time_from')) {
                $table->dropColumn('booking_time_from');
            }
        });
    }
};
