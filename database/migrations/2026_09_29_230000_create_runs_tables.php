<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('performed_on');
            $table->time('started_at')->nullable();
            $table->string('type', 20);
            $table->decimal('distance_miles', 6, 2);
            $table->unsignedInteger('duration_seconds');
            $table->integer('elevation_gain_feet')->nullable();
            $table->unsignedInteger('calories')->nullable();
            $table->unsignedSmallInteger('average_heart_rate')->nullable();
            $table->unsignedSmallInteger('max_heart_rate')->nullable();
            $table->smallInteger('temperature_fahrenheit')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('run_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->decimal('distance_miles', 5, 2);
            $table->unsignedInteger('duration_seconds');
            $table->integer('elevation_feet')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('run_splits');
        Schema::dropIfExists('runs');
    }
};
