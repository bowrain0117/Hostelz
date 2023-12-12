<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('country', 70);
                $table->string('continent', 100);
                $table->string('regionType', 70);
                $table->string('currencyCode', 3);
                $table->integer('geonamesCountryID')->unsigned();
                $table->integer('cityCount');
                $table->text('notes');
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
        Schema::dropIfExists('countries');
    }
}
