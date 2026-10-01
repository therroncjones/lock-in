<?php

use App\Support\SetMeasure;
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
            $table->string('measure', 20)->default(SetMeasure::Reps)->after('group');
        });

        $timed = [
            'Bar Hang',
            'Copenhagen Plank',
            'Cycling',
            'Farmer Carry',
            'Front Hold',
            'Hollow Hold',
            'Jump Rope',
            'Kneeling Plank',
            'Kneeling Side Plank',
            'One-Handed Bar Hang',
            'Plank',
            'Rowing',
            'Side Plank',
            'Weighted Plank',
        ];

        DB::table('exercises')->whereIn('name', $timed)->update(['measure' => SetMeasure::Time]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn('measure');
        });
    }
};
