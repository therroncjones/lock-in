<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_sets', function (Blueprint $table) {
            $table->boolean('completed')->default(false)->after('reps');
        });
    }

    public function down(): void
    {
        Schema::table('workout_sets', function (Blueprint $table) {
            $table->dropColumn('completed');
        });
    }
};
