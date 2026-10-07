<?php

namespace Tests\Feature\Filament;

use App\Enums\FinancialTermDifficulty;
use App\Filament\Resources\FinancialTerms\FinancialTermResource;
use App\Filament\Resources\FinancialTerms\Pages\CreateFinancialTerm;
use App\Filament\Resources\FinancialTerms\Pages\EditFinancialTerm;
use App\Filament\Resources\FinancialTerms\Pages\ListFinancialTerms;
use App\Models\FinancialTerm;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialTermResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_admin_can_access_catalog_list(): void
    {
        $admin = User::factory()->admin()->create();
        $term = FinancialTerm::factory()->create(['term' => 'Liquidez']);

        $response = $this->actingAs($admin)->get(FinancialTermResource::getUrl('index'));

        $response->assertSee($term->term);
    }

    public function test_participant_cannot_access_catalog_resource(): void
    {
        $participant = User::factory()->participant()->create();

        $response = $this->actingAs($participant)->get(FinancialTermResource::getUrl('index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_a_term(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateFinancialTerm::class)
            ->fillForm([
                'term' => 'Educação Financeira',
                'description' => 'Conhecimentos para tomar decisões conscientes sobre dinheiro.',
                'difficulty' => FinancialTermDifficulty::Hard->value,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseHas('financial_terms', [
            'term' => 'Educação Financeira',
            'normalized_term' => 'EDUCACAOFINANCEIRA',
            'difficulty' => FinancialTermDifficulty::Hard->value,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_edit_a_term_and_normalization_is_refreshed(): void
    {
        $admin = User::factory()->admin()->create();
        $term = FinancialTerm::factory()->create(['term' => 'Credito']);
        $this->actingAs($admin);

        Livewire::test(EditFinancialTerm::class, ['record' => $term->getRouteKey()])
            ->fillForm([
                'term' => 'Crédito Consciente',
                'description' => 'Uso planejado de crédito conforme a capacidade de pagamento.',
                'difficulty' => FinancialTermDifficulty::Medium->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('financial_terms', [
            'id' => $term->id,
            'term' => 'Crédito Consciente',
            'normalized_term' => 'CREDITOCONSCIENTE',
        ]);
    }

    public function test_admin_can_deactivate_and_reactivate_a_term(): void
    {
        $admin = User::factory()->admin()->create();
        $term = FinancialTerm::factory()->active()->create(['term' => 'Reserva']);
        $this->actingAs($admin);
        $component = Livewire::test(EditFinancialTerm::class, ['record' => $term->getRouteKey()]);

        $component
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertFalse($term->refresh()->is_active);

        $component
            ->fillForm(['is_active' => true])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertTrue($term->refresh()->is_active);
    }

    public function test_admin_cannot_delete_a_term_and_can_deactivate_it_instead(): void
    {
        $admin = User::factory()->admin()->create();
        $term = FinancialTerm::factory()->create(['term' => 'Tarifa']);
        $this->actingAs($admin);

        Livewire::test(ListFinancialTerms::class)
            ->assertTableActionDoesNotExist('delete', record: $term)
            ->assertCanSeeTableRecords([$term]);

        $this->assertFalse($admin->can('delete', $term));

        Livewire::test(EditFinancialTerm::class, ['record' => $term->getRouteKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('financial_terms', [
            'id' => $term->id,
            'is_active' => false,
        ]);
    }

    public function test_equivalent_term_is_rejected_by_form_validation(): void
    {
        $admin = User::factory()->admin()->create();
        FinancialTerm::factory()->create(['term' => 'Ação']);
        $this->actingAs($admin);

        Livewire::test(CreateFinancialTerm::class)
            ->fillForm([
                'term' => 'ACAO',
                'description' => 'Tentativa de duplicação sem acento.',
                'difficulty' => FinancialTermDifficulty::Easy->value,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['term'])
            ->assertNotNotified();

        $this->assertDatabaseCount('financial_terms', 1);
    }

    public function test_admin_can_search_and_filter_catalog(): void
    {
        $admin = User::factory()->admin()->create();
        $liquidity = FinancialTerm::factory()->active()->create([
            'term' => 'Liquidez',
            'difficulty' => FinancialTermDifficulty::Medium,
        ]);
        $volatility = FinancialTerm::factory()->inactive()->create([
            'term' => 'Volatilidade',
            'difficulty' => FinancialTermDifficulty::Hard,
        ]);
        $this->actingAs($admin);

        Livewire::test(ListFinancialTerms::class)
            ->assertCanSeeTableRecords([$liquidity, $volatility])
            ->searchTable('LIQUIDEZ')
            ->assertCanSeeTableRecords([$liquidity])
            ->assertCanNotSeeTableRecords([$volatility])
            ->searchTable('')
            ->filterTable('difficulty', FinancialTermDifficulty::Hard->value)
            ->assertCanSeeTableRecords([$volatility])
            ->assertCanNotSeeTableRecords([$liquidity])
            ->removeTableFilter('difficulty')
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$volatility])
            ->assertCanNotSeeTableRecords([$liquidity]);
    }

    public function test_description_is_escaped_on_view_page(): void
    {
        $admin = User::factory()->admin()->create();
        $term = FinancialTerm::factory()->create([
            'term' => 'Segurança',
            'description' => '<script>alert("xss")</script> Educação financeira segura.',
        ]);

        $response = $this->actingAs($admin)->get(FinancialTermResource::getUrl('view', ['record' => $term]));

        $response
            ->assertSee('&lt;script&gt;', escape: false)
            ->assertDontSee('<script>alert("xss")</script>', escape: false);
    }
}
