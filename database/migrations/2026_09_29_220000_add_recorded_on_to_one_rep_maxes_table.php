<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('one_rep_maxes', 'recorded_on')) {
            Schema::table('one_rep_maxes', function (Blueprint $table) {
                $table->date('recorded_on')->nullable()->after('exercise_id');
            });

            DB::table('one_rep_maxes')->orderBy('id')->lazy()->each(function (object $row): void {
                DB::table('one_rep_maxes')->where('id', $row->id)->update([
                    'recorded_on' => Carbon::parse($row->created_at)->toDateString(),
                ]);
            });

            Schema::table('one_rep_maxes', function (Blueprint $table) {
                $table->date('recorded_on')->nullable(false)->change();
            });
        }

        Schema::table('one_rep_maxes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'exercise_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'exercise_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        $latestIds = DB::table('one_rep_maxes')
            ->selectRaw('max(id) as id')
            ->groupBy('user_id', 'exercise_id')
            ->pluck('id');

        DB::table('one_rep_maxes')->whereNotIn('id', $latestIds)->delete();

        Schema::table('one_rep_maxes', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'exercise_id', 'recorded_on']);
            $table->dropColumn('recorded_on');
            $table->unique(['user_id', 'exercise_id']);
        });
    }
};
