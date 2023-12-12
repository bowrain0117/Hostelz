<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDataCorrectionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('dataCorrection')) {
            Schema::create('dataCorrection', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('dbTable');
                $table->string('dbField')->index();
                $table->string('contextValue1');
                $table->string('contextValue2');
                $table->string('oldValue')->index();
                $table->string('newValue')->index();
            });
        }

        // Fulltext index (for suggestions when doing a mass data correction)
        if (! collect(DB::select('SHOW INDEXES FROM dataCorrection'))->pluck('Key_name')->contains('correctionsIndex')) {
            DB::statement('CREATE FULLTEXT INDEX correctionsIndex ON dataCorrection (newValue, oldValue)');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('dataCorrection');
    }
}
