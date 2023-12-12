<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpiderResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('spiderResults')) {
            Schema::create('spiderResults', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('domain', 100)->index();
                $table->string('url', 500);
                $table->string('type', 50);
                $table->date('lastUpdateDate');
                $table->text('spiderResults');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function down(): void
    {
        if (Schema::hasTable('spiderResults')) {
            Schema::drop('spiderResults');
        }
    }
}
