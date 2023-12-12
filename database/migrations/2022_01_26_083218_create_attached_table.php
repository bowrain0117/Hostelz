<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('attached')) {
            Schema::create('attached', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('subjectType', 100);
                $table->integer('subjectID');
                $table->string('subjectString', 200);
                $table->string('type', 100);
                $table->string('source', 100);
                $table->string('language', 6);
                $table->string('status', 25)->index();
                $table->integer('score'); // TO DO: This seems to be only used as 0 or 100 depending on whether we have checked for similar text.  Probably should make this checkStatus or something, or just use plagiarismCheckDate since we do both at the same time i think?
                $table->date('plagiarismCheckDate')->nullable();
                $table->integer('plagiarismPercent');
                $table->text('plagiarismInfo');
                $table->date('lastUpdate')->nullable();
                $table->text('dataBeforeEditing');
                $table->text('data');
                $table->text('notes');
                $table->text('comments');
                $table->integer('userID')->unsigned()->index();
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
        Schema::dropIfExists('attached');
    }
}
