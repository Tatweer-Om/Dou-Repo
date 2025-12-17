<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_area_id')->nullable()->after('order_type');
            $table->unsignedBigInteger('delivery_city_id')->nullable()->after('delivery_area_id');
            $table->text('delivery_address')->nullable()->after('delivery_city_id');
            $table->decimal('delivery_fee', 10, 3)->default(0)->after('delivery_address');
        });
    }

    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_area_id','delivery_city_id','delivery_address','delivery_fee']);
        });
    }
};
