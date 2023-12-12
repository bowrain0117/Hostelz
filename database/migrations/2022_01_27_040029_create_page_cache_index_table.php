<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Lib\PageCache;

class CreatePageCacheIndexTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('pageCacheIndex')) {
            Schema::create('pageCacheIndex', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('cacheKey', 32); // (multi-key index created below)
                $table->string('tag', 20);
                $table->datetime('expirationTime')->nullable();
            });

            DB::statement('ALTER TABLE pageCacheIndex ADD UNIQUE INDEX(tag(20), cacheKey(32))');
        }

        for ($i = 0; $i <= 99; $i++) {
            File::makeDirectory('/storage/pageCache/' . $i, 0770, true, true);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasTable('pageCacheIndex')) {
            PageCache::clearAll();
            Schema::drop('pageCacheIndex');
        }

        PageCache::removeCacheStorage();
    }
}
