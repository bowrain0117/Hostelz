<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSearchRanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('searchRanks')) {
            Schema::create('searchRanks', function (Blueprint $table): void {
                $table->increments('id');
                $table->date('checkDate');
                $table->string('source', 50);
                $table->string('searchPhrase', 200);
                $table->integer('rank');
                $table->string('placeType', 50);
                $table->integer('placeID');
                $table->string('placeString', 70);
                $table->index(['placeType', 'placeID', 'placeString']);
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
        Schema::dropIfExists('searchRanks');
    }
}
