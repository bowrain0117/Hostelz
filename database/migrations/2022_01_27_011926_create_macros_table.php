<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMacrosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('macros')) {
            Schema::create('macros', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('status', 25);
                $table->integer('userID');
                $table->string('userHasPermission', 100);
                $table->string('purpose', 100);
                $table->string('category', 100);
                $table->string('conditions', 1000); // sets of "if variable = value" pairs
                $table->string('name', 100);
                $table->string('macroText', 1000);
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
        Schema::dropIfExists('macros');
    }
}
