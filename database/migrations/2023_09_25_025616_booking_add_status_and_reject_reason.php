<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('status', 25)->after('userID')->nullable();
            $table->string('reject_reason')->after('status')->nullable();
            $table->string('label')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['status']);
            $table->dropColumn(['reject_reason']);
            $table->dropColumn(['label']);
        });
    }
};
