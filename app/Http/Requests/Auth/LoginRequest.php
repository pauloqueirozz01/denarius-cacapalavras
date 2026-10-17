<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        RateLimiter::hit($this->ipThrottleKey(), config('denarius.auth.login.decay_seconds'));

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), config('denarius.auth.login.decay_seconds'));

            throw ValidationException::withMessages([
                'email' => 'Não foi possível entrar com as credenciais informadas.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $limitedKey = match (true) {
            RateLimiter::tooManyAttempts($this->throttleKey(), config('denarius.auth.login.max_attempts')) => $this->throttleKey(),
            RateLimiter::tooManyAttempts($this->ipThrottleKey(), config('denarius.auth.login.ip_max_attempts')) => $this->ipThrottleKey(),
            default => null,
        };

        if ($limitedKey === null) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($limitedKey);

        throw ValidationException::withMessages([
            'email' => "Muitas tentativas. Tente novamente em {$seconds} segundos.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')->toString()).'|'.$this->ip());
    }

    /**
     * Ceiling shared by everyone behind the same IP, high enough for a whole
     * event room on one Wi-Fi network and low enough to stop a flood.
     */
    public function ipThrottleKey(): string
    {
        return 'login-ip:'.$this->ip();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->string('email')->trim()->lower()->toString(),
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe sua senha.',
        ];
    }
}
