<?php

namespace Tests\Feature\Filament;

use App\Enums\GameSessionStatus;
use App\Filament\Resources\GameSessions\GameSessionResource;
use App\Filament\Resources\GameSessions\Pages\ListGameSessions;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\GameSession;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReadOnlyAdminResourcesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_admin_can_search_users_and_see_best_completed_score_without_mutation_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $participant = User::factory()->participant()->create([
            'name' => 'Participante de QA',
            'email' => 'qa@example.test',
        ]);
        GameSession::factory()->completed()->for($participant)->create(['score' => 900]);
        GameSession::factory()->completed()->for($participant)->create(['score' => 600]);
        GameSession::factory()->abandoned()->for($participant)->create(['score' => 9_999]);
        $this->actingAs($admin);

        $response = $this->get(UserResource::getUrl('index'));
        $response->assertOk()->assertSee('Usuários');

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$admin, $participant])
            ->searchTable('qa@example.test')
            ->assertCanSeeTableRecords([$participant])
            ->assertCanNotSeeTableRecords([$admin])
            ->assertSee('900');

        $this->assertFalse(UserResource::canCreate());
        $this->assertFalse(UserResource::canEdit($participant));
        $this->assertFalse(UserResource::canDelete($participant));
    }

    public function test_participant_cannot_access_user_admin_resource(): void
    {
        $participant = User::factory()->participant()->create();

        $this->actingAs($participant)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_user_name_is_escaped_in_admin_table(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->participant()->create([
            'name' => '<script>alert("xss")</script>',
        ]);

        $response = $this->actingAs($admin)->get(UserResource::getUrl('index'));

        $response
            ->assertOk()
            ->assertSee('&lt;script&gt;', escape: false)
            ->assertDontSee('<script>alert("xss")</script>', escape: false);
    }

    public function test_admin_can_filter_and_view_game_sessions_without_snapshot_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $participant = User::factory()->participant()->create(['name' => 'Partida QA']);
        $completed = GameSession::factory()->completed()->for($participant)->create([
            'score' => 1_200,
            'found_words_count' => 1,
            'total_words' => 1,
            'grid' => [['S', 'E', 'G', 'R', 'E', 'D', 'O']],
        ]);
        GameSession::factory()->abandoned()->for($participant)->create();
        $this->actingAs($admin);

        Livewire::test(ListGameSessions::class)
            ->assertCanSeeTableRecords([$completed])
            ->filterTable('status', GameSessionStatus::Completed->value)
            ->assertCanSeeTableRecords([$completed]);

        $response = $this->get(GameSessionResource::getUrl('view', ['record' => $completed]));
        $response->assertOk()
            ->assertSee('Partida QA')
            ->assertSee('1.200')
            ->assertDontSee('SEGREDO');

        $this->assertFalse(GameSessionResource::canCreate());
        $this->assertFalse(GameSessionResource::canEdit($completed));
        $this->assertFalse(GameSessionResource::canDelete($completed));
    }

    public function test_participant_cannot_access_game_session_admin_resource(): void
    {
        $participant = User::factory()->participant()->create();

        $this->actingAs($participant)
            ->get(GameSessionResource::getUrl('index'))
            ->assertForbidden();
    }
}
