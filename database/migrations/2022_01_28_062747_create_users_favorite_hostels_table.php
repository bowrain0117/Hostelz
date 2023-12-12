<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersFavoriteHostelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('users_favorite_hostels')) {
            Schema::create('users_favorite_hostels', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id');
                $table->integer('listing_id');
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
        Schema::dropIfExists('users_favorite_hostels');
    }
}
