<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateIncomingLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('incomingLinks')) {
            Schema::create('incomingLinks', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('url', 500);
                $table->string('domain', 100)->index();
                $table->string('linksTo', 500);
                $table->string('anchorText', 250);
                $table->char('followable', 1); // 'y', 'n', or '' if unknown
                $table->string('source', 500);
                $table->date('createDate');
                $table->date('lastCheck')->nullable();
                $table->string('checkStatus', 25);
                $table->string('pageTitle', 500);
                $table->string('language', 6);
                $table->string('pagerank', 10);
                $table->integer('domainAuthority')->nullable();
                $table->integer('pageAuthority')->nullable();
                $table->integer('trafficRank')->nullable();
                $table->text('spiderResults');
                $table->string('otherWebsitesLinked', 500);
                $table->string('category', 50);
                $table->string('name', 100);
                $table->integer('priorityLevel');
                $table->string('contactStatus', 50);
                $table->string('contactStatusSpecific', 50);
                $table->string('contactEmails', 500);
                $table->string('invalidEmails', 500);
                $table->string('contactFormURL', 100);
                $table->string('contactTopics', 500);
                $table->date('lastContact')->nullable();
                $table->string('followUpStatus', 20);
                $table->date('reminderDate')->nullable();
                $table->integer('userID');
                $table->string('mailingLists', 500);
                $table->text('notes');
                $table->string('placeType', 50);
                $table->integer('placeID');
                $table->string('placeString', 70); // used for ContinentInfo and Region
                $table->index(['placeType', 'placeID', 'placeString']);
            });
        }

        if (! Schema::hasColumn('incomingLinks', 'otherWebsitesLinked')) {
            DB::statement('ALTER TABLE incomingLinks ADD `otherWebsitesLinked` VARCHAR(2000) NOT NULL');
        }
        if (! Schema::hasColumn('incomingLinks', 'contactEmails')) {
            DB::statement('ALTER TABLE incomingLinks ADD `contactEmails` VARCHAR(2000) NOT NULL');
        }
        if (! Schema::hasColumn('incomingLinks', 'invalidEmails')) {
            DB::statement('ALTER TABLE incomingLinks ADD `invalidEmails` VARCHAR(2000) NOT NULL');
        }
        if (! Schema::hasColumn('incomingLinks', 'contactTopics')) {
            DB::statement('ALTER TABLE incomingLinks ADD `contactTopics` VARCHAR(2000) NOT NULL');
        }
        if (! Schema::hasColumn('incomingLinks', 'mailingLists')) {
            DB::statement('ALTER TABLE incomingLinks ADD `mailingLists` VARCHAR(2000) NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('incomingLinks');
    }
}
