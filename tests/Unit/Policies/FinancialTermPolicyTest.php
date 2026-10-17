<?php

namespace Tests\Unit\Policies;

use App\Models\FinancialTerm;
use App\Models\User;
use App\Policies\FinancialTermPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FinancialTermPolicyTest extends TestCase
{
    #[DataProvider('adminAbilities')]
    public function test_admin_is_allowed_to_manage_the_catalog(string $ability): void
    {
        $admin = User::factory()->admin()->make();
        $term = FinancialTerm::factory()->make();
        $policy = new FinancialTermPolicy;

        $this->assertTrue($policy->{$ability}($admin, ...$this->argumentsFor($ability, $term)));
    }

    #[DataProvider('adminAbilities')]
    public function test_participant_is_forbidden_from_managing_the_catalog(string $ability): void
    {
        $participant = User::factory()->participant()->make();
        $term = FinancialTerm::factory()->make();
        $policy = new FinancialTermPolicy;

        $this->assertFalse($policy->{$ability}($participant, ...$this->argumentsFor($ability, $term)));
    }

    public function test_term_deletion_is_disabled_for_all_roles(): void
    {
        $admin = User::factory()->admin()->make();
        $term = FinancialTerm::factory()->make();
        $policy = new FinancialTermPolicy;

        $this->assertFalse($policy->delete($admin, $term));
        $this->assertFalse($policy->deleteAny($admin));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function adminAbilities(): array
    {
        return [
            'list' => ['viewAny'],
            'view' => ['view'],
            'create' => ['create'],
            'update' => ['update'],
        ];
    }

    /**
     * @return array<int, FinancialTerm>
     */
    private function argumentsFor(string $ability, FinancialTerm $term): array
    {
        return in_array($ability, ['view', 'update'], true) ? [$term] : [];
    }
}
