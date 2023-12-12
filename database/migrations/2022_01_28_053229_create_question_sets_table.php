<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('questionSets')) {
            Schema::create('questionSets', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('setName', 100)->index();
                $table->string('requireAccess', 100);
                $table->text('questions');
                $table->text('notes');
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
        Schema::dropIfExists('questionSets');
    }
}
