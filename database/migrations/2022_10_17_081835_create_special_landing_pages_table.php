<?php

use App\Enums\StatusSlp;
use App\Models\SpecialLandingPage;
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
        if (! Schema::hasTable('special_landing_pages')) {
            Schema::create('special_landing_pages', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->morphs('subjectable');
                $table->string('status', 25)->default(StatusSlp::Draft->value);
                $table->string('language', 6)->default('en');
                $table->tinyInteger('number_featured_hostels')->default(SpecialLandingPage::FEATURED_HOSTELS);

                $table->string('title');
                $table->string('meta_title');
                $table->text('meta_description');

                $table->string('slug');

                $table->text('content');
                $table->text('notes');

                $table->string('category');

                $table->timestamps();
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
        Schema::dropIfExists('special_landing_pages');
    }
};
