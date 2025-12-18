<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old columns if they exist
        if (Schema::hasColumn('customers', 'governorate')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('governorate');
            });
        }
        
        if (Schema::hasColumn('customers', 'area') && !Schema::hasColumn('customers', 'area_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('area');
            });
        }
        
        // Add new columns if they don't exist
        if (!Schema::hasColumn('customers', 'city_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedBigInteger('city_id')->nullable()->after('phone');
            });
        }
        
        if (!Schema::hasColumn('customers', 'area_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedBigInteger('area_id')->nullable()->after('city_id');
            });
        }
        
        if (!Schema::hasColumn('customers', 'notes')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->longText('notes')->nullable()->after('area_id');
            });
        }
        
        // Add foreign keys - try to add them, ignore if they already exist
        if (Schema::hasColumn('customers', 'city_id')) {
            try {
                DB::statement('ALTER TABLE `customers` ADD CONSTRAINT `customers_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL');
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }
        }
        
        if (Schema::hasColumn('customers', 'area_id')) {
            try {
                DB::statement('ALTER TABLE `customers` ADD CONSTRAINT `customers_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL');
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys if they exist
        try {
            DB::statement('ALTER TABLE `customers` DROP FOREIGN KEY `customers_city_id_foreign`');
        } catch (\Exception $e) {
            // Foreign key doesn't exist, ignore
        }
        
        try {
            DB::statement('ALTER TABLE `customers` DROP FOREIGN KEY `customers_area_id_foreign`');
        } catch (\Exception $e) {
            // Foreign key doesn't exist, ignore
        }
        
        // Drop new columns if they exist
        if (Schema::hasColumn('customers', 'city_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('city_id');
            });
        }
        
        if (Schema::hasColumn('customers', 'area_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('area_id');
            });
        }
        
        if (Schema::hasColumn('customers', 'notes')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
        
        // Restore old columns if they don't exist
        if (!Schema::hasColumn('customers', 'governorate')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('governorate')->nullable()->after('phone');
            });
        }
        
        if (!Schema::hasColumn('customers', 'area')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('area')->nullable()->after('governorate');
            });
        }
    }
};
