<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingClicksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('bookingClicks')) {
            Schema::create('bookingClicks', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('importedID')->index();
                $table->datetime('clickTime');
                $table->string('origination', 500);
                $table->integer('affiliateID');
                $table->integer('userID');
                $table->string('trackingCode', 250);
                $table->string('email', 255);
                $table->string('language', 7);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('bookingClicks');
    }
}
