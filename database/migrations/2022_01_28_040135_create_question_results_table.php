<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('questionResults')) {
            Schema::create('questionResults', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('questionSetID');
                $table->integer('userID');
                $table->string('email', 150);
                $table->string('referenceCode', 100);
                $table->text('results');
                $table->datetime('startTime');
                $table->string('ipAddress', 50);
                $table->text('staffNotes');
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
        Schema::dropIfExists('questionResults');
    }
}
