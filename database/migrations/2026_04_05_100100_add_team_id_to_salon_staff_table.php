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

        Schema::table('salon_staff', function (Blueprint $table) {
            if (!Schema::hasColumn('salon_staff', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('email')->constrained('salon_teams')->nullOnDelete();
            }
        });

        if ($gh && Schema::hasColumn('salon_staff', 'team')) {
            DB::table('salon_staff')->where('team', 1)->update(['team_id' => $gh]);
            DB::table('salon_staff')->where('team', 2)->update(['team_id' => $sb]);
        }

        if (Schema::hasColumn('salon_staff', 'team')) {
            Schema::table('salon_staff', function (Blueprint $table) {
                $table->dropColumn('team');
            });
        }
    }

    public function down(): void
    {
        Schema::table('salon_staff', function (Blueprint $table) {
            if (Schema::hasColumn('salon_staff', 'team_id')) {
                $table->dropForeign(['team_id']);
                $table->dropColumn('team_id');
            }
            if (!Schema::hasColumn('salon_staff', 'team')) {
                $table->integer('team')->nullable()->after('email');
            }
        });
    }
};
