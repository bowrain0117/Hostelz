<?php

use App\Models\Pic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateListingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
//        Pic::make100DirsCloud('hostels', 'owner', ['originals']);
//        Pic::make100DirsLocal('listingThumbnails');
//        Pic::make100DirsLocal('listingThumbnails/webp');
//        Pic::make100DirsCloud('hostels', 'panorama', ['originals']);

        if (! Schema::hasTable('listings')) {
            Schema::create('listings', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('propertyType', 40);
                $table->boolean('propertyTypeVerified');
                $table->string('continent', 60);
                $table->string('country', 70)->index();
                $table->string('region', 70);
                $table->string('city', 70)->index();
                $table->string('cityAlt', 70);
                $table->string('address', 130)->index();
                $table->string('mapAddress', 130);
                $table->string('poBox', 100);
                $table->string('zipcode', 30);
                $table->string('mailingAddress', 500);
                $table->string('name', 80)->index();
                $table->string('videoURL', 250);
                $table->text('videoEmbedHTML');
                $table->text('videoSchema');
                $table->string('managerEmail', 255);
                $table->string('supportEmail', 255);
                $table->string('bookingsEmail', 255);
                $table->string('importedEmail', 500);
                $table->text('invalidEmails');
                $table->string('ownerName', 255);
                $table->string('web', 200);
                $table->string('websiteDomain', 200);
                $table->integer('webStatus');
                $table->integer('webDisplay');
                $table->string('tel', 150);
                $table->string('fax', 150);
                $table->text('mgmtFeatures');
                $table->string('mgmtBacklink', 250);
                $table->text('compiledFeatures');
                $table->text('specialNote');
                $table->date('lastUpdate')->nullable();
                $table->datetime('lastUpdated')->nullable(); // last time any change was made to the listing that cleared its PageCache.
                $table->integer('verified');
                $table->integer('contactStatus');
                $table->date('dateAdded');
                $table->text('comment');
                $table->integer('featuredListingPriority');
                $table->integer('boutiqueHostel');
                $table->boolean('blockSnippet');
                $table->boolean('useForBookingPrice')->default(true);
                $table->string('featured')->default('no');
                $table->integer('onlineReservations');
                $table->integer('unavailableCount');
                $table->integer('preferredBooking');
                $table->string('ourBookingSystem', 40);
                $table->string('roomTypes', 250);
                $table->decimal('privatePrice', 7, 2);
                $table->decimal('sharedPrice', 7, 2);
                $table->tinyInteger('combinedRating');
                $table->integer('combinedRatingCount');
                $table->string('contentScores', 500);
                $table->decimal('overallContentScore');
                $table->string('lastEditSessionID', 255);
                $table->decimal('ownerLatitude', 10, 6);
                $table->decimal('ownerLongitude', 10, 6);
                $table->boolean('geocodingLocked');
                $table->decimal('latitude', 10, 6);
                $table->decimal('longitude', 10, 6);
                $table->enum('locationStatus', ['', 'ok', 'outlier', 'confusion']);
                $table->string('source', 255);
                $table->integer('targetListing');
                $table->string('stickerStatus', 100);
                $table->date('stickerDate')->nullable();
                $table->string('stickerPlacement', 100);
                $table->string('panoramaStatus', 100);
                $table->integer('hostels_chain_id')->unsigned();
            });
        }

        if (! Schema::hasColumn('listings', 'supportEmail')) {
            DB::statement('ALTER TABLE listings ADD `supportEmail` VARCHAR(2000) NOT NULL');
        }
        if (! Schema::hasColumn('listings', 'managerEmail')) {
            DB::statement('ALTER TABLE listings ADD `managerEmail` VARCHAR(2000) NOT NULL');
        }
        if (! Schema::hasColumn('listings', 'bookingsEmail')) {
            DB::statement('ALTER TABLE listings ADD `bookingsEmail` VARCHAR(2000) NOT NULL');
        }
        if (! Schema::hasColumn('listings', 'importedEmail')) {
            DB::statement('ALTER TABLE listings ADD `importedEmail` VARCHAR(2000) NOT NULL');
        }
        if (! Schema::hasColumn('listings', 'invalidEmails')) {
            DB::statement('ALTER TABLE listings ADD `invalidEmails` VARCHAR(2000) NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
}
