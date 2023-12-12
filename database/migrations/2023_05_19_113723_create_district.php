<?php

use App\Enums\District\Type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cityId');
            $table->string('name');
            $table->string('type')->default(Type::In->value);
            $table->string('slug');
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_city_centre')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
