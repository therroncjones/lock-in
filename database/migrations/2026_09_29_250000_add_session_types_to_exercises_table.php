<?php

use Database\Seeders\ExerciseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->json('session_types')->nullable()->after('group');
        });

        DB::table('exercises')->orderBy('id')->lazy()->each(function (object $exercise): void {
            $types = ExerciseSeeder::typesFor($exercise->name, $exercise->group);

            DB::table('exercises')->where('id', $exercise->id)->update([
                'session_types' => json_encode($types),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn('session_types');
        });
    }
};
