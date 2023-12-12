<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWishlistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('wishlists')) {
            Schema::create('wishlists', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index();
                $table->string('name');
                $table->boolean('isShared')->default(false);

                $table->timestamps();
            });
        }

        if (! Schema::hasTable('listing_wishlist')) {
            Schema::create('listing_wishlist', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('wishlist_id')->unsigned();
                $table->unsignedInteger('listing_id');

                $table->foreign('wishlist_id')
                      ->references('id')->on('wishlists')
                      ->onDelete('cascade');

                $table->foreign('listing_id')
                      ->references('id')->on('listings')
                      ->onDelete('cascade');

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
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('listing_wishlist');
    }
}
