<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $credentials = [
            'name' => config('denarius.admin.name'),
            'email' => config('denarius.admin.email'),
            'password' => config('denarius.admin.password'),
        ];

        if (collect($credentials)->contains(fn (mixed $value): bool => blank($value))) {
            $this->command?->warn('Administrador não criado: configure ADMIN_NAME, ADMIN_EMAIL e ADMIN_PASSWORD.');

            return;
        }

        $validated = Validator::make($credentials, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ])->validate();

        $email = Str::lower(trim($validated['email']));

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        $admin = new User;
        $admin->name = trim($validated['name']);
        $admin->email = $email;
        $admin->password = $validated['password'];
        $admin->role = UserRole::Admin;
        $admin->save();
    }
}
