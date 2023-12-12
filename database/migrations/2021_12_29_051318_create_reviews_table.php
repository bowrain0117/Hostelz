<?php

use App\Models\Pic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
//        Pic::make100DirsCloud('reviews', '', ['originals']);

        if (! Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('reviewerID');
                $table->integer('hostelID')->index();
                $table->string('status', 25);
                $table->date('expirationDate')->nullable();
                $table->date('reviewDate')->nullable();
                $table->string('language', 6);
                $table->integer('rating');
                $table->text('review');
                $table->text('editedReview');
                $table->text('ownerResponse');
                $table->string('author', 75);
                $table->string('bookingInfo', 250);
                $table->text('comment');
                $table->text('staffComment');
                $table->text('comments');
                $table->boolean('newStaffComment');
                $table->boolean('newReviewerComment');
                $table->date('plagiarismCheckDate')->nullable();
                $table->integer('plagiarismPercent');
                $table->text('plagiarismInfo');
                $table->text('notes');
                $table->string('payStatus', 32);
                $table->boolean('rereviewWanted');
            });
        }

        DB::statement('ALTER TABLE `reviews` CHANGE `review` `review` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;');
        DB::statement('ALTER TABLE `reviews` CHANGE `editedReview` `editedReview` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `reviews` CHANGE `review` `review` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;');
        DB::statement('ALTER TABLE `reviews` CHANGE `editedReview` `editedReview` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;');
    }
}
