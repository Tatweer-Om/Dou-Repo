<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable()->after('area');
            $table->unsignedBigInteger('city_id')->nullable()->after('area_id');
            $table->text('address')->nullable()->after('city_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['area_id','city_id','address']);
        });
    }
};
