<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Game\ClaimGuestGameAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, ClaimGuestGameAction $claimGuestGame): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        try {
            $claimGuestGame->execute($request->user());
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->intended(route('game', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
