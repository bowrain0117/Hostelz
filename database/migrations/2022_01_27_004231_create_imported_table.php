<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateImportedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        //  todo: temp
//        Pic::make100DirsLocal('imported/listings/tiny');
//        Pic::make100DirsLocal('imported/listings/big');
//        Pic::make100DirsLocal('imported/listings/thumbnails');
//        Pic::make100DirsLocal('imported/listings/webp_big');
//        Pic::make100DirsLocal('imported/listings/webp_thumbnails');

        if (! Schema::hasTable('imported')) {
            Schema::create('imported', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('status', 25);
                $table->integer('hostelID')->index();
                $table->integer('version');
                $table->string('system', 100);
                $table->integer('intCode')->index();
                $table->string('charCode', 255);
                $table->string('urlLink', 255);
                $table->boolean('availability');
                $table->boolean('checkedAvail');
                $table->string('name', 100);
                $table->string('previousName', 100);
                $table->string('address1', 255);
                $table->string('address2', 255);
                $table->string('city', 150);
                $table->string('region', 70);
                $table->string('zipcode', 150);
                $table->string('country', 150);
                $table->integer('theirCityCode');
                $table->decimal('latitude', 10, 6);
                $table->decimal('longitude', 10, 6);
                $table->string('email', 255);
                $table->string('web', 255);
                $table->string('tel', 255);
                $table->string('fax', 255);
                $table->text('other');
                $table->text('features');
                $table->text('pics');
                $table->string('propertyType', 50);
                $table->integer('maxPeople');
                $table->integer('maxNights'); /* (not yet used for anything) */
                $table->integer('arrivalEarliest');
                $table->integer('arrivalLatest');
                $table->string('localCurrency', 3);
                $table->decimal('privatePrice', 7, 2);
                $table->decimal('sharedPrice', 7, 2);
                $table->decimal('bedPriceUSD', 7, 2);
                $table->decimal('bedPriceEUR', 7, 2);
                $table->decimal('bedPriceGBP', 7, 2);
                $table->string('rating', 1000);
                $table->integer('specialCode');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
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
        Schema::dropIfExists('imported');
    }
}
