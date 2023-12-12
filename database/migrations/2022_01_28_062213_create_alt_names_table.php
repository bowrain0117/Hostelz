<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAltNamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('altNames')) {
            Schema::create('altNames', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('source', 15); // currently is always 'geonames'
                $table->integer('geonamesID')->index();
                $table->string('language', 7);
                $table->string('altName', 200)->index();
                $table->boolean('isPreferredName');
                $table->boolean('isShortName');
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
        Schema::dropIfExists('altNames');
    }
}
