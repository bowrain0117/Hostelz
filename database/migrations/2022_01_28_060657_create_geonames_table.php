<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeonamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('geonames')) {
            Schema::create('geonames', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name', 200)->index();
                $table->decimal('latitude', 18, 15)->index();
                $table->decimal('longitude', 18, 15)->index();
                $table->string('featureClass', 15); // Our class name (Region, Water, Land, etc.)
                $table->string('featureCode', 50); // Our feature code (ADM1, Bay, Gulf, etc.)
                $table->string('theirFeatureCode', 10); // Their feature code (not really used)
                $table->string('countryCode', 2)->index();
                $table->string('adminDiv1', 20);
                $table->string('adminDiv2', 80);
                $table->string('adminDiv3', 20);
                $table->string('adminDiv4', 20);
                $table->integer('population');
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
        Schema::dropIfExists('geonames');
    }
}
