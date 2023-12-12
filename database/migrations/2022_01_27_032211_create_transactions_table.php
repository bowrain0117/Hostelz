<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table): void {
                $table->increments('id');
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3);
                $table->string('type', 30);
                $table->string('description', 100)->default('');
                $table->integer('payment_method_id')->default(0);
                $table->text('extra_data');
                $table->integer('user_id')->index();
                $table->timestamps();
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
        Schema::dropIfExists('transactions');
    }
}
