<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavedListListingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('savedListListings')) {
            Schema::create('savedListListings', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('savedList_id')->unsigned()->index();
                $table->integer('listing_id')->unsigned()->index();
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
        if (Schema::hasTable('savedListListings')) {
            Schema::drop('savedListListings');
        }

        Schema::dropIfExists('savedListListings');
    }
}
