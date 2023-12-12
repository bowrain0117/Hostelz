<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('eventLog')) {
            Schema::create('eventLog', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('category', 50);
                $table->string('action', 255);
                $table->string('subjectType', 255);
                $table->integer('subjectID')->unsigned()->index();
                $table->string('subjectString', 255);
                $table->integer('userID')->unsigned()->index();
                $table->text('data');
                $table->string('ipAddress', 50);
                $table->string('sessionID', 255);
                $table->datetime('eventTime');
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
        Schema::dropIfExists('eventLog');
    }
}
