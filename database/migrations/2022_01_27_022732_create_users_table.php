<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('username', 120)->index(); // to do: change this to 'email'
                $table->string('passwordHash', 70); // Laravel Auth says it needs 60 chars for password hashes.
                $table->string('localEmailAddress', 120); // this is also used as their outgoing email address
                $table->string('alsoGetLocalEmailFor', 1000); // also get email for these addresses (but not used as their outgoing email address)
                $table->string('paymentEmail', 120);
                $table->string('access', 500);
                $table->text('payAmounts');
                $table->datetime('lastPaid')->nullable();
                $table->date('birthDate')->nullable()->default(null);
                $table->string('countries', 250); // country codes for what translations this staff user can work on -> TO DO: Rename to "languages"
                $table->string('status', 25);
                $table->integer('points');
                $table->string('name', 150);
                $table->string('nickname', 150);
                $table->string('slug');
                $table->string('homeCountry', 80);
                $table->date('dateAdded');
                $table->text('formData');
                $table->text('data');
                $table->text('bio');
                $table->string('sessionID', 255);
                $table->text('affiliateURLs');
                $table->string('mgmtListings', 1000)->index();
                $table->string('invalidEmails', 500);
                $table->string('apiAccess', 250);
                $table->string('apiKey', 16);
                $table->rememberToken(); // (adds a "remember_token" field used for "remember me" login sessions)
                $table->boolean('isPublic')->default(0);
                $table->char('gender', 1)->default('0');
                $table->string('languages')->default('en');
                $table->string('facebook', 255);
                $table->string('instagram', 255);
                $table->string('tiktok', 255);
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
        Schema::dropIfExists('users');
    }
}
