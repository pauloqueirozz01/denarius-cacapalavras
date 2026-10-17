<?php

use App\Http\Middleware\AddSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AddSecurityHeaders::class);
        $middleware->redirectGuestsTo(fn (): string => route('login'));
        $middleware->redirectUsersTo(fn (): string => route('game'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Expired CSRF token (419): a repeated logout already did its job, and
        // auth forms go back with the typed data instead of a dead-end page.
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            if ($request->routeIs('logout')) {
                return redirect()->route('home');
            }

            if ($request->routeIs('login.store', 'register.store')) {
                return redirect()
                    ->route($request->routeIs('login.store') ? 'login' : 'register')
                    ->withInput($request->except(['password', 'password_confirmation', '_token']))
                    ->withErrors(['email' => 'Sua sessão expirou. Confira os dados e envie novamente.']);
            }

            return null;
        });
    })->create();
