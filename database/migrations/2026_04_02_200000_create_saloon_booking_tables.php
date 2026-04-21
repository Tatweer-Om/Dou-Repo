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
        Schema::create('saloon_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_no')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('salon_customers')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('salon_staff')->nullOnDelete();
            $table->date('booking_date')->nullable();
            $table->time('booking_time')->nullable();
            $table->unsignedInteger('total_services')->default(0);
            $table->decimal('total_services_amount', 20, 3)->default(0);
            $table->decimal('total_paid', 20, 3)->default(0);
            $table->decimal('total_remaining', 20, 3)->default(0);
            $table->string('status')->default('confirmed');
            $table->text('special_notes')->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('saloon_booking_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saloon_booking_id')->constrained('saloon_bookings')->cascadeOnDelete();
            $table->json('services_json');
            $table->unsignedInteger('services_count')->default(0);
            $table->decimal('services_total_amount', 20, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('saloon_booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saloon_booking_id')->constrained('saloon_bookings')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('payment_method');
            $table->decimal('amount', 20, 3)->default(0);
            $table->dateTime('payment_at')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('saloon_booking_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saloon_booking_id')->constrained('saloon_bookings')->cascadeOnDelete();
            $table->string('action_type');
            $table->json('snapshot');
            $table->dateTime('action_at');
            $table->string('action_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saloon_booking_histories');
        Schema::dropIfExists('saloon_booking_payments');
        Schema::dropIfExists('saloon_booking_details');
        Schema::dropIfExists('saloon_bookings');
    }
};
