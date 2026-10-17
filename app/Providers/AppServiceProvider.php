<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('registration', function (Request $request): array {
            $decayMinutes = config('denarius.auth.registration.decay_minutes');
            $email = Str::transliterate(Str::lower(trim((string) $request->input('email'))));

            return [
                Limit::perMinutes($decayMinutes, config('denarius.auth.registration.email_max_attempts'))
                    ->by("register-email:{$email}|{$request->ip()}")
                    ->response(fn (Request $request, array $headers): RedirectResponse => back()
                        ->withInput($request->except(['password', 'password_confirmation']))
                        ->withErrors([
                            'email' => 'Muitas tentativas de cadastro. Tente novamente em '.($headers['Retry-After'] ?? 60).' segundos.',
                        ])),
                Limit::perMinutes($decayMinutes, config('denarius.auth.registration.ip_max_attempts'))
                    ->by("register-ip:{$request->ip()}"),
            ];
        });
    }
}
