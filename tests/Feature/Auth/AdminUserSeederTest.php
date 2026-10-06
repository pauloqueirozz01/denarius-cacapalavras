<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_is_not_created_when_configuration_is_incomplete(): void
    {
        config()->set('denarius.admin', [
            'name' => 'Admin Denarius',
            'email' => null,
            'password' => null,
        ]);

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_valid_configuration_creates_admin_with_hashed_password(): void
    {
        config()->set('denarius.admin', [
            'name' => 'Admin Denarius',
            'email' => 'ADMIN@DENARIUS.LOCAL',
            'password' => 'Local!Admin123',
        ]);

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->sole();
        $this->assertSame('admin@denarius.local', $admin->email);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(Hash::check('Local!Admin123', $admin->password));
    }

    public function test_repeated_seed_does_not_overwrite_existing_user(): void
    {
        $existingAdmin = User::factory()->admin()->create([
            'name' => 'Admin Original',
            'email' => 'admin@denarius.local',
        ]);
        config()->set('denarius.admin', [
            'name' => 'Nome substituto',
            'email' => 'admin@denarius.local',
            'password' => 'Local!Admin123',
        ]);

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('Admin Original', $existingAdmin->fresh()->name);
    }

    public function test_weak_admin_password_is_rejected(): void
    {
        config()->set('denarius.admin', [
            'name' => 'Admin Denarius',
            'email' => 'admin@denarius.local',
            'password' => 'weak',
        ]);

        $this->expectException(ValidationException::class);

        $this->seed(AdminUserSeeder::class);
    }
}
