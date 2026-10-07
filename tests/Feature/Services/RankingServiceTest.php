<?php

namespace Tests\Feature\Services;

use App\Data\LeaderboardEntry;
use App\Enums\GameSessionStatus;
use App\Models\GameSession;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RankingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_includes_only_completed_participant_sessions_and_reads_persisted_score(): void
    {
        $eligibleUser = User::factory()->participant()->create();
        $admin = User::factory()->admin()->create();
        $this->completedSession($eligibleUser, ['score' => 37, 'found_words_count' => 1]);
        GameSession::factory()->for($eligibleUser)->active()->create(['score' => 999999]);
        GameSession::factory()->for($eligibleUser)->abandoned()->create(['score' => 999999]);
        $this->completedSession($admin, ['score' => 999999]);

        $page = app(RankingService::class)->paginate();

        $this->assertSame(1, $page->total());
        $this->assertSame(37, $page->items()[0]->score);
        $this->assertSame($eligibleUser->name, $page->items()[0]->name);
        $this->assertSame(1, app(RankingService::class)->totalParticipants());
    }

    public function test_selects_one_best_completed_attempt_per_user(): void
    {
        $user = User::factory()->participant()->create();
        $best = $this->completedSession($user, ['score' => 1500, 'duration_seconds' => 120]);
        $this->completedSession($user, ['score' => 1400, 'duration_seconds' => 20]);
        $this->completedSession($user, ['score' => 1000, 'duration_seconds' => 10]);

        $entry = app(RankingService::class)->positionForUser($user->id);

        $this->assertInstanceOf(LeaderboardEntry::class, $entry);
        $this->assertSame($best->id, $entry->sessionId);
        $this->assertSame(1500, $entry->score);
        $this->assertSame(1, app(RankingService::class)->totalParticipants());
    }

    public function test_orders_equal_scores_by_duration_then_oldest_finish_then_stable_session_id(): void
    {
        $slowUser = User::factory()->participant()->create();
        $fastUser = User::factory()->participant()->create();
        $laterUser = User::factory()->participant()->create();
        $earlierUser = User::factory()->participant()->create();
        $tieUser = User::factory()->participant()->create();
        $sameFinishedAt = '2026-10-01 12:00:00';

        $slowSession = $this->completedSession($slowUser, [
            'score' => 1000,
            'duration_seconds' => 60,
            'finished_at' => $sameFinishedAt,
        ]);
        $this->completedSession($fastUser, [
            'score' => 1000,
            'duration_seconds' => 50,
            'finished_at' => $sameFinishedAt,
        ]);
        $this->completedSession($laterUser, [
            'score' => 1000,
            'duration_seconds' => 50,
            'finished_at' => '2026-10-01 12:00:01',
        ]);
        $earlierSession = $this->completedSession($earlierUser, [
            'score' => 1000,
            'duration_seconds' => 50,
            'finished_at' => $sameFinishedAt,
        ]);
        $tieSession = $this->completedSession($tieUser, [
            'score' => 1000,
            'duration_seconds' => 50,
            'finished_at' => $sameFinishedAt,
        ]);
        $this->completedSession($tieUser, [
            'score' => 1000,
            'duration_seconds' => 50,
            'finished_at' => $sameFinishedAt,
        ]);

        $entries = app(RankingService::class)->paginate()->items();

        $this->assertSame([
            $fastUser->id,
            $earlierUser->id,
            $tieUser->id,
            $laterUser->id,
            $slowUser->id,
        ], array_map(static fn (LeaderboardEntry $entry): int => $entry->userId, $entries));
        $this->assertTrue($earlierSession->id < $tieSession->id);
        $this->assertSame($tieSession->id, $entries[2]->sessionId);
        $this->assertSame(5, $entries[4]->position);
        $this->assertSame($slowSession->id, $entries[4]->sessionId);
    }

    public function test_places_null_duration_and_finish_timestamps_after_known_values_deterministically(): void
    {
        $knownUser = User::factory()->participant()->create();
        $unknownDurationUser = User::factory()->participant()->create();
        $unknownFinishUser = User::factory()->participant()->create();
        $knownSession = $this->completedSession($knownUser, [
            'score' => 500,
            'duration_seconds' => 20,
            'finished_at' => '2026-10-01 12:00:00',
        ]);
        $unknownDurationSession = $this->completedSession($unknownDurationUser, [
            'score' => 500,
            'duration_seconds' => 20,
            'finished_at' => '2026-10-01 12:00:00',
        ]);
        $unknownFinishSession = $this->completedSession($unknownFinishUser, [
            'score' => 500,
            'duration_seconds' => 20,
            'finished_at' => '2026-10-01 12:00:00',
        ]);
        DB::table('game_sessions')->where('id', $unknownDurationSession->id)->update(['duration_seconds' => null]);
        DB::table('game_sessions')->where('id', $unknownFinishSession->id)->update(['finished_at' => null]);

        $entries = app(RankingService::class)->paginate()->items();

        $this->assertSame($knownSession->id, $entries[0]->sessionId);
        $this->assertSame($unknownFinishSession->id, $entries[1]->sessionId);
        $this->assertSame($unknownDurationSession->id, $entries[2]->sessionId);
        $this->assertNull($entries[1]->finishedAt);
        $this->assertNull($entries[2]->durationSeconds);
    }

    public function test_finds_player_position_outside_top_page_and_returns_null_without_completed_game(): void
    {
        $users = User::factory()->participant()->count(23)->create();
        foreach ($users as $index => $user) {
            $this->completedSession($user, [
                'score' => 1000 - $index,
                'duration_seconds' => 60,
            ]);
        }

        $service = app(RankingService::class);
        $firstPage = $service->paginate(perPage: 20, page: 1);
        $lastPage = $service->paginate(perPage: 20, page: 2);
        $position = $service->positionForUser($users[22]->id);
        $newUser = User::factory()->participant()->create();

        $this->assertCount(20, $firstPage->items());
        $this->assertSame(23, $lastPage->items()[2]->position);
        $this->assertSame(23, $position->position);
        $this->assertNull($service->positionForUser($newUser->id));
    }

    public function test_position_for_completed_session_is_the_users_best_rank_not_that_attempts_rank(): void
    {
        $user = User::factory()->participant()->create();
        $bestSession = $this->completedSession($user, ['score' => 1200]);
        $lowerSession = $this->completedSession($user, ['score' => 1000]);

        $entry = app(RankingService::class)->positionForCompletedSession($lowerSession->id);

        $this->assertSame($bestSession->id, $entry->sessionId);
        $this->assertSame(1200, $entry->score);
        $this->assertNull(app(RankingService::class)->positionForCompletedSession(999999));
    }

    public function test_uses_session_id_as_the_final_global_tiebreaker(): void
    {
        $higherIdUser = User::factory()->participant()->create();
        $lowerIdUser = User::factory()->participant()->create();
        $tie = [
            'score' => 1000,
            'duration_seconds' => 60,
            'finished_at' => '2026-10-01 12:00:00',
        ];

        $higherIdSession = $this->completedSession($higherIdUser, $tie + ['id' => 9001]);
        $lowerIdSession = $this->completedSession($lowerIdUser, $tie + ['id' => 9000]);

        $entries = app(RankingService::class)->paginate()->items();

        $this->assertSame([$lowerIdSession->id, $higherIdSession->id], array_map(
            static fn (LeaderboardEntry $entry): int => $entry->sessionId,
            $entries,
        ));
        $this->assertSame([1, 2], array_map(
            static fn (LeaderboardEntry $entry): int => $entry->position,
            $entries,
        ));
    }

    public function test_newly_completed_results_appear_on_the_next_read_without_cached_ranking_state(): void
    {
        $service = app(RankingService::class);
        $this->assertSame(0, $service->totalParticipants());

        $user = User::factory()->participant()->create();
        $session = $this->completedSession($user, ['score' => 800]);

        $this->assertSame($session->id, $service->paginate()->items()[0]->sessionId);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function completedSession(User $user, array $attributes = []): GameSession
    {
        $startedAt = now()->subMinutes(2);
        $attributes = array_merge([
            'status' => GameSessionStatus::Completed,
            'score' => 100,
            'total_words' => 1,
            'found_words_count' => 1,
            'duration_seconds' => 60,
            'started_at' => $startedAt,
            'finished_at' => $startedAt->copy()->addSeconds(60),
        ], $attributes);

        return GameSession::factory()->for($user)->completed()->create($attributes);
    }
}
