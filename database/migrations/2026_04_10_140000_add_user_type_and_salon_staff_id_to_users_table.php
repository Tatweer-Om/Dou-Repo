<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type', 20)->default('admin')->after('user_scope');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'salon_staff_id')) {
                $table->foreignId('salon_staff_id')->nullable()->unique()->after('user_type')->constrained('salon_staff')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'salon_staff_id')) {
                $table->dropForeign(['salon_staff_id']);
                $table->dropColumn('salon_staff_id');
            }
            if (Schema::hasColumn('users', 'user_type')) {
                $table->dropColumn('user_type');
            }
        });
    }
};
