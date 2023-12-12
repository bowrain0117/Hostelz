<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRedirectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('old_url');
            $table->string('new_url');
            $table->string('note');
            $table->string('encoded_url', 512);
            $table->string('tag');
            $table->char('type', 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
}
