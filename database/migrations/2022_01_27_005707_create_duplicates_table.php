<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDuplicatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('duplicates')) {
            Schema::create('duplicates', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('status', 25);
                $table->integer('listingID')->index();
                $table->integer('otherListing')->index(); // Note: listingID is always < otherListing
                $table->string('source', 100);
                $table->integer('score');
                $table->integer('userID');
                $table->text('notes');
                $table->integer('priorityLevel');
                $table->integer('maxChoiceDifficulty');
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
        Schema::dropIfExists('duplicates');
    }
}
