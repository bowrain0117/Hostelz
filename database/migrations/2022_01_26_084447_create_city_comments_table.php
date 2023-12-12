<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCityCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('cityComments')) {
            Schema::create('cityComments', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('status', 20);
                $table->integer('cityID');
                $table->string('language', 6);
                $table->string('name', 80);
                $table->text('comment');
                $table->string('originalComment');
                $table->string('ipAddress', 50);
                $table->string('sessionID', 255);
                $table->integer('userID');
                $table->date('commentDate');
                $table->text('notes');
                $table->string('bayesianBucket', 70);
                $table->integer('bayesianScore');
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
        Schema::dropIfExists('cityComments');
    }
}
