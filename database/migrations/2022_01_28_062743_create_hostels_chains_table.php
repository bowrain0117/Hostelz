<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostelsChainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('hostels_chains')) {
            Schema::create('hostels_chains', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description');
                $table->boolean('isActive')->default(false);

                $table->string('meta_title');
                $table->text('meta_description');
                $table->text('affiliate_links');
                $table->string('website_link');
                $table->string('instagram_link');
                $table->string('videoURL', 250);
                $table->text('videoEmbedHTML');
                $table->text('videoSchema');

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
        Schema::dropIfExists('hostels_chains');
        Schema::table('listings', function (Blueprint $table): void {
            $table->dropColumn('hostels_chain_id');
        });
    }
}
