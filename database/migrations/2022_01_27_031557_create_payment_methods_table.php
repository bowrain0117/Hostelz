<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('status', 20);
                $table->string('name', 50)->default('');
                $table->string('type', 30);
                $table->string('data', 255);
                $table->integer('precedence');
                $table->integer('user_id')->unsigned();
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
        Schema::dropIfExists('payment_methods');
    }
}
