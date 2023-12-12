<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('status', 20);
                $table->integer('hostelID');
                $table->integer('rating');
                $table->string('language', 6);
                $table->string('name', 80);
                $table->string('homeCountry', 80);
                $table->integer('age');
                $table->string('summary', 70);
                $table->text('comment');
                $table->text('originalComment');
                $table->text('ownerResponse');
                $table->text('notes');
                $table->string('ipAddress', 50);
                $table->string('sessionID', 255);
                $table->integer('ourBookingID');
                $table->string('bookingID', 30);
                $table->integer('userID')->index();
                $table->date('commentDate');
                $table->string('email', 255);
                $table->tinyInteger('emailVerified');
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
        Schema::dropIfExists('comments');
    }
}
