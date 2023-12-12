<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeocodeCacheTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('geocodeCache')) {
            Schema::create('geocodeCache', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('accuracy');
                $table->string('addressString', 500);
                $table->decimal('latitude', 10, 6)->index();
                $table->decimal('longitude', 10, 6);
                $table->string('country', 150);
                $table->string('region', 150);
                $table->string('area', 150);
                $table->string('area2', 150);
                $table->string('colloquialArea', 150);
                $table->string('city', 150);
                $table->string('cityArea', 150);
                $table->string('neighborhood', 150);
                $table->string('streetName', 150);
                $table->string('source', 25);
                $table->date('dateAdded');
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
        Schema::dropIfExists('geocodeCache');
    }
}
