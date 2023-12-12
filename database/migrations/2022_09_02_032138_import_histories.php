<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('import_histories')) {
            Schema::create('import_histories', function (Blueprint $table): void {
                $table->id();
                $table->string('system');
                $table->integer('checked')->default(0);
                $table->integer('inserted')->default(0);
                $table->json('options')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('finished_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('import_histories');
    }
};
