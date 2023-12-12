<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavedListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('savedLists')) {
            Schema::create('savedLists', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index();
                $table->string('name');
                $table->boolean('isShared');
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
        if (Schema::hasTable('savedLists')) {
            Schema::drop('savedLists');
        }

        Schema::dropIfExists('savedLists');
    }
}
