<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('ads')) {
            Schema::create('ads', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('status', 100);
                $table->string('name', 100);
                $table->string('placementType', 20)->index();
                $table->integer('userID');
                $table->string('linkURL', 250);
                $table->string('adText', 500);
                $table->integer('viewsRemaining'); // or -1 for unlimited
                $table->date('startDate')->nullable(); // not yet using
                $table->date('endDate')->nullable(); // not yet using
                $table->integer('viewsPerDay');
                $table->integer('viewsToday');
                $table->text('notes');
                $table->text('comments'); // TO DO: remove the next 3 fields (not used)
                $table->boolean('newStaffComment');
                $table->boolean('newUserComment');
                $table->integer('incomingLinkID')->unsigned();
                $table->string('placeType', 50);
                $table->integer('placeID');
                $table->string('placeString', 70); // used for ContinentInfo and Region
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
        Schema::dropIfExists('ads');
    }
}
