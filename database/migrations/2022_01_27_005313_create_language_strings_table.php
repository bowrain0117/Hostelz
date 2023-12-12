<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLanguageStringsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('languageStrings')) {
            Schema::create('languageStrings', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('language', 10);
                $table->string('group', 80);
                $table->string('key', 80)->index();
                $table->string('text', 1000);
                $table->string('originalText', 1000); // the English that the translation was based on, so we can track changes.
                $table->integer('userID');
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
        Schema::dropIfExists('languageStrings');
    }
}
