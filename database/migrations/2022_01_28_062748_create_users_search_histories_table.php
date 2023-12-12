<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersSearchHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('users_search_histories')) {
            Schema::create('users_search_histories', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id');
                $table->string('query');
                $table->string('category');
                $table->integer('itemId');
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
        Schema::dropIfExists('users_search_histories');
    }
}
