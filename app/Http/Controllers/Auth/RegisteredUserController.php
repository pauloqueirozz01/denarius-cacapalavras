<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Game\ClaimGuestGameAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(RegisterRequest $request, ClaimGuestGameAction $claimGuestGame): RedirectResponse
    {
        try {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Este e-mail já está cadastrado.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        try {
            $claimGuestGame->execute($user);
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('game');
    }
}
