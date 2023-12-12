<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCityInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('cityInfo')) {
            Schema::create('cityInfo', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('continent', 100);
                $table->string('country', 70);
                $table->string('region', 70);
                $table->string('cityGroup', 70);
                $table->string('city', 70);
                $table->string('cityAlt', 70);
                $table->string('postalCode', 70);
                $table->text('tips');
                $table->text('links');
                $table->string('infoLink', 100);
                $table->string('weatherImage', 150);
                $table->string('weatherLink', 150);
                $table->integer('mapCRC')->unsigned();
                $table->decimal('latitude', 7, 4);
                $table->decimal('longitude', 7, 4);
                $table->string('nearbyCities', 500);
                $table->text('staffNotes');
                $table->integer('hostelCount');
                $table->integer('totalListingCount');
                $table->integer('gnCityID');
                $table->integer('gnRegionID');
                $table->integer('gnCountryID');
                $table->boolean('displaysRegion');
                $table->text('poi');
                $table->integer('topRatedHostel');
                $table->integer('cheapestHostel');
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
        Schema::dropIfExists('cityInfo');
    }
}
