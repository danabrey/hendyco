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
        Schema::table('booking_clients', function (Blueprint $table) {
            $table->foreignId('gift_card_id')->nullable()->after('payment_method')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gift_card_id');
        });
    }
};
