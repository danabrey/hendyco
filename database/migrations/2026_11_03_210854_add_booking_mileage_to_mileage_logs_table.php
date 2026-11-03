<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mileage_logs', function (Blueprint $table) {
            $table->boolean('booking_mileage')->default(false)->after('booking_id');
        });

        DB::table('bookings')
            ->join('services', 'services.id', '=', 'bookings.service_id')
            ->whereNull('bookings.deleted_at')
            ->where('bookings.miles', '>', 0)
            ->select('bookings.id', 'bookings.date', 'bookings.miles', 'bookings.created_at', 'bookings.updated_at', 'services.name as service_name')
            ->orderBy('bookings.id')
            ->each(function (object $booking): void {
                DB::table('mileage_logs')->insert([
                    'date' => $booking->date,
                    'miles' => $booking->miles,
                    'purpose' => 'Booking: '.$booking->service_name,
                    'booking_id' => $booking->id,
                    'booking_mileage' => true,
                    'created_at' => $booking->created_at,
                    'updated_at' => $booking->updated_at,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mileage_logs')->where('booking_mileage', true)->delete();

        Schema::table('mileage_logs', function (Blueprint $table) {
            $table->dropColumn('booking_mileage');
        });
    }
};
