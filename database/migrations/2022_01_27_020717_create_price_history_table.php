<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreatePriceHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('priceHistory')) {
            Schema::create('priceHistory', function ($table): void {
                $table->increments('id');
                $table->integer('listingID')->index();
                $table->date('month');
                $table->string('roomType', 10);
                $table->integer('peoplePerRoom');
                $table->decimal('averagePricePerNight', 7, 2);
                $table->integer('dataPointsInAverage');
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
        Schema::dropIfExists('priceHistory');
    }
}
