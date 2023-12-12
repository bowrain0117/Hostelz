<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('userID')->index();
                $table->datetime('bookingTime');
                $table->date('startDate')->nullable();
                $table->date('endDate')->nullable();
                $table->integer('nights');
                $table->integer('people');
                $table->string('language', 7);
                $table->string('system', 100);
                $table->string('email', 255)->index();
                $table->string('firstName', 100);
                $table->string('lastName', 100);
                $table->string('phone', 40);
                $table->string('nationality', 100);
                $table->enum('gender', ['Male', 'Female', 'Mixed']);
                $table->integer('arrivalTime');
                $table->string('bookingID', 50);
                $table->string('internalBookingID', 250);
                $table->integer('importedID');
                $table->integer('listingID')->index();
                $table->decimal('depositUSD', 7, 2);
                $table->decimal('commission', 7, 2);
                $table->integer('affiliateID')->index();
                $table->decimal('affiliateCommission', 7, 2);
                $table->text('bookingDetails');
                $table->text('messageText');
                $table->string('origination', 500);
            });
        }

        if (! Schema::hasColumn('bookings', 'invalidEmails')) {
            DB::statement('ALTER TABLE bookings ADD `invalidEmails` VARCHAR(2000) NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
}
