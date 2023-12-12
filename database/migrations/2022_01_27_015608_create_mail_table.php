<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('mail')) {
            Schema::create('mail', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('status', 25);
                $table->text('sender');
                $table->string('senderAddress', 255);
                $table->text('recipient');
                $table->text('cc');
                $table->string('bcc', 255);
                $table->string('recipientAddresses', 255);
                $table->string('subject', 255);
                $table->string('ipAddress', 50);
                $table->text('headers');
                $table->text('bodyText');
                $table->text('comment');
                $table->datetime('transmitTime');
                $table->date('reminderDate')->nullable();
                $table->integer('userID');
                $table->integer('listingID')->index();
                $table->integer('senderTrust');
                $table->integer('spamicity');
            });
        }

        if (! Schema::hasColumn('mail', 'recipientAddresses')) {
            DB::statement('ALTER TABLE mail ADD `recipientAddresses` VARCHAR(2000) NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('mail');
    }
}
