<?php

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
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(0)->after('found_words_count');
        });

        $scoringConfiguration = [
            'points_per_word' => 100,
            'completion_bonus' => 500,
            'speed_bonus_tiers' => [
                ['up_to_seconds' => 120, 'points' => 500],
                ['up_to_seconds' => 180, 'points' => 300],
                ['up_to_seconds' => 300, 'points' => 150],
            ],
        ];

        DB::table('game_sessions')
            ->select(['id', 'status', 'found_words_count', 'duration_seconds', 'generation_config'])
            ->orderBy('id')
            ->chunkById(100, function ($sessions) use ($scoringConfiguration): void {
                foreach ($sessions as $session) {
                    $score = (int) $session->found_words_count * $scoringConfiguration['points_per_word'];

                    if ($session->status === 'completed') {
                        $score += $scoringConfiguration['completion_bonus'];
                        $score += $this->speedBonus(
                            durationSeconds: (int) $session->duration_seconds,
                            tiers: $scoringConfiguration['speed_bonus_tiers'],
                        );
                    }

                    $generationConfig = json_decode((string) $session->generation_config, true);
                    $generationConfig = is_array($generationConfig) ? $generationConfig : [];
                    $generationConfig['scoring'] = $scoringConfiguration;

                    DB::table('game_sessions')
                        ->where('id', $session->id)
                        ->update([
                            'score' => $score,
                            'generation_config' => json_encode($generationConfig, JSON_THROW_ON_ERROR),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }

    /**
     * @param  list<array{up_to_seconds: int, points: int}>  $tiers
     */
    private function speedBonus(int $durationSeconds, array $tiers): int
    {
        foreach ($tiers as $tier) {
            if ($durationSeconds <= $tier['up_to_seconds']) {
                return $tier['points'];
            }
        }

        return 0;
    }
};
