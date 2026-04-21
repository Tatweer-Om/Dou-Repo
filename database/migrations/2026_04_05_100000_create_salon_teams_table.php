<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_teams', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('salon_teams')->insert([
            [
                'code' => 'ghubrah',
                'name' => 'Al Ghubrah',
                'name_ar' => 'الغبرة',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'seeb',
                'name' => 'Seeb',
                'name_ar' => 'السيب',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_teams');
    }
};
