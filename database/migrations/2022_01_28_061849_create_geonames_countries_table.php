<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeonamesCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('geonamesCountries')) {
            Schema::create('geonamesCountries', function (Blueprint $table): void {
                $table->char('countryCode', 2);
                $table->primary('countryCode');
                $table->string('country', 200)->index();
                $table->char('continentCode', 2);
                $table->string('currencyCode', 3);
                $table->string('currencyName', 100);
                $table->string('postalRegex', 100);
                $table->string('languages', 100);
                $table->integer('geonamesID');
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
        Schema::dropIfExists('geonamesCountries');
    }
}
