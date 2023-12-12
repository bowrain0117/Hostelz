<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListingSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('listing_subscriptions')) {
            Schema::create('listing_subscriptions', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('status', 100); // "active", "expired"
                $table->string('type', 100); // "featured"
                $table->decimal('price', 7, 2);
                $table->string('currency', 3);
                $table->date('end_date');
                $table->boolean('auto_renew');
                $table->integer('user_id')->unsigned()->index();
                $table->integer('listing_id')->unsigned()->index();
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
        Schema::dropIfExists('listing_subscriptions');
    }
}
