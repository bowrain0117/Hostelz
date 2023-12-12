<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('userID');
                $table->string('status', 25);
                $table->date('submitDate')->nullable();
                $table->date('publishDate')->nullable();
                $table->date('updateDate')->nullable();
                $table->string('language', 6);
                $table->string('title', 255);
                $table->string('metaTitle', 255);
                $table->text('metaDescription');
                $table->string('authorName', 255);
                $table->text('proposal');
                $table->text('originalArticle');
                $table->text('finalArticle');
                $table->text('comments');
                $table->boolean('newStaffComment');
                $table->boolean('newUserComment');
                $table->text('notes');
                $table->string('payStatus', 32);
                $table->string('placementType', 50);
                $table->string('placement', 255);
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
        Schema::dropIfExists('articles');
    }
}
