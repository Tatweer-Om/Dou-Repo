<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $gh = DB::table('salon_teams')->where('code', 'ghubrah')->value('id');
        $sb = DB::table('salon_teams')->where('code', 'seeb')->value('id');

        Schema::table('saloon_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('saloon_bookings', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('customer_id')->constrained('salon_teams')->nullOnDelete();
            }
        });

        if ($gh && $sb && Schema::hasColumn('saloon_bookings', 'team')) {
            // Compare as CHAR only — never `team = 1` (integer) or MySQL coerces text rows like "gobra" and errors.
            foreach (['1', '01'] as $v) {
                DB::table('saloon_bookings')
                    ->whereRaw('TRIM(CAST(`team` AS CHAR(191) CHARACTER SET utf8mb4)) = ?', [$v])
                    ->update(['team_id' => $gh]);
            }
            foreach (['2', '02'] as $v) {
                DB::table('saloon_bookings')
                    ->whereRaw('TRIM(CAST(`team` AS CHAR(191) CHARACTER SET utf8mb4)) = ?', [$v])
                    ->update(['team_id' => $sb]);
            }
            foreach (['ghubrah', 'gobra', 'al ghubrah', 'al_ghubrah'] as $alias) {
                DB::table('saloon_bookings')
                    ->whereRaw('LOWER(TRIM(CAST(`team` AS CHAR(191) CHARACTER SET utf8mb4))) = ?', [strtolower($alias)])
                    ->update(['team_id' => $gh]);
            }
            DB::table('saloon_bookings')
                ->whereRaw('LOWER(TRIM(CAST(`team` AS CHAR(191) CHARACTER SET utf8mb4))) = ?', ['seeb'])
                ->update(['team_id' => $sb]);
        }

        if (Schema::hasColumn('saloon_bookings', 'team')) {
            Schema::table('saloon_bookings', function (Blueprint $table) {
                $table->dropColumn('team');
            });
        }
    }

    public function down(): void
    {
        Schema::table('saloon_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('saloon_bookings', 'team_id')) {
                $table->dropForeign(['team_id']);
                $table->dropColumn('team_id');
            }
            if (!Schema::hasColumn('saloon_bookings', 'team')) {
                $table->string('team')->nullable()->after('customer_id');
            }
        });
    }
};
