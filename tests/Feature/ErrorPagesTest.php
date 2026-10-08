<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_expired_session_page_is_in_ptbr_with_a_way_back(): void
    {
        Route::middleware('web')->get('/_test/expired', fn () => abort(419));

        $this->get('/_test/expired')
            ->assertStatus(419)
            ->assertSee('Sua sessão expirou')
            ->assertSee('Recarregue a página e tente de novo.')
            ->assertSee('Voltar e tentar de novo')
            ->assertDontSee('Page Expired');
    }

    public function test_too_many_requests_page_tells_how_long_to_wait(): void
    {
        Route::middleware('web')->get('/_test/throttled', fn () => abort(429, '', ['Retry-After' => 42]));

        $this->get('/_test/throttled')
            ->assertTooManyRequests()
            ->assertSee('Muitas tentativas em pouco tempo')
            ->assertSee('Aguarde 42 segundos e tente de novo.')
            ->assertDontSee('Too Many Requests');
    }

    public function test_server_error_page_is_in_ptbr_and_hides_technical_details(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/_test/boom', fn () => throw new RuntimeException('segredo interno SQLSTATE'));

        $this->get('/_test/boom')
            ->assertInternalServerError()
            ->assertSee('Algo deu errado do nosso lado')
            ->assertDontSee('segredo interno')
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('Server Error');
    }

    public function test_maintenance_page_is_in_ptbr(): void
    {
        Route::middleware('web')->get('/_test/maintenance', fn () => abort(503));

        $this->get('/_test/maintenance')
            ->assertServiceUnavailable()
            ->assertSee('Voltamos em instantes')
            ->assertDontSee('Service Unavailable');
    }
}
