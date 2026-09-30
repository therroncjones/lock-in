<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $workoutIds = DB::table('workout_blocks')->distinct()->pluck('workout_id');

        foreach ($workoutIds as $workoutId) {
            $blocks = DB::table('workout_blocks')
                ->where('workout_id', $workoutId)
                ->orderBy('position')
                ->get();

            $index = 0;

            foreach ($blocks as $block) {
                if (! preg_match('/^Block \d+$/', $block->name)) {
                    continue;
                }

                $letter = $index < 26 ? chr(ord('A') + $index) : (string) ($index + 1);

                DB::table('workout_blocks')->where('id', $block->id)->update([
                    'name' => 'Block '.$letter,
                ]);

                $index++;
            }
        }
    }

    public function down(): void
    {
        $workoutIds = DB::table('workout_blocks')->distinct()->pluck('workout_id');

        foreach ($workoutIds as $workoutId) {
            $blocks = DB::table('workout_blocks')
                ->where('workout_id', $workoutId)
                ->orderBy('position')
                ->get();

            $index = 1;

            foreach ($blocks as $block) {
                if (! preg_match('/^Block [A-Z]$/', $block->name)) {
                    continue;
                }

                DB::table('workout_blocks')->where('id', $block->id)->update([
                    'name' => 'Block '.$index,
                ]);

                $index++;
            }
        }
    }
};
