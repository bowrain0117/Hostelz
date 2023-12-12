<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersDreamDestinationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('users_dream_destinations')) {
            Schema::create('users_dream_destinations', function (Blueprint $table): void {
                $table->increments('id');

                $table->integer('user_id');
                $table->integer('city_id');

                /*            $table->foreign('user_id')
                                  ->references('id')->on('users')
                                  ->onDelete('cascade');

                            $table->foreign('listing_id')
                                  ->references('id')->on('listings')
                                  ->onDelete('cascade');*/

                $table->timestamps();
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
        Schema::dropIfExists('users_dream_destinations');
    }
}
