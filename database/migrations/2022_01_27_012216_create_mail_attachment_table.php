<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CreateMailAttachmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
//        minDelayBetweenCalls('MailAttachment:storageApiCalls', 50); // (miliseconds)
//        Storage::disk('spaces1')->makeDirectory('mail-attachments');
//        for ($i = 0; $i <= 99; $i++) {
//            minDelayBetweenCalls('MailAttachment:storageApiCalls', 50); // (miliseconds)
//            Storage::disk('spaces1')->makeDirectory('mail-attachments/'.$i);
//        }

        if (! Schema::hasTable('mailAttachment')) {
            Schema::create('mailAttachment', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('mailID')->index();
                $table->string('filename', 255);
                $table->string('mimeType', 255);
                $table->integer('size');
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
        Schema::dropIfExists('mailAttachment');
    }
}
