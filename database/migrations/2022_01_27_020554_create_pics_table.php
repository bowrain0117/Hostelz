<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        //todo: temp
//        Pic::make100DirsLocal('hostels/owner/tiny');
//        Pic::make100DirsLocal('hostels/owner/big');
//        Pic::make100DirsLocal('hostels/owner/thumbnails');
//        Pic::make100DirsLocal('hostels/owner/webp_big');
//        Pic::make100DirsLocal('hostels/owner/webp_thumbnails');

        if (! Schema::hasTable('pics')) {
            Schema::create('pics', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('status', 25);
                $table->integer('subjectID')->index();
                $table->string('subjectType', 100);
                $table->string('type', 100);
                $table->string('source', 100);
                $table->integer('picNum');
                $table->boolean('isPrimary');
                $table->boolean('featuredPhoto')->default(false);
                $table->string('originalFiletype', 32);
                $table->integer('originalWidth');
                $table->integer('originalHeight');
                $table->decimal('originalAspect', 4);
                $table->integer('originalFilesize');
                $table->string('originalMD5hash', 32);
                $table->date('imageSearchCheckDate')->nullable();
                $table->text('imageSearchMatches');
                $table->string('caption', 250);
                $table->string('edits', 500);
                $table->string('storageTypes', 500);
                $table->date('lastUpdate')->nullable();
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
        Schema::dropIfExists('pics');
    }
}
