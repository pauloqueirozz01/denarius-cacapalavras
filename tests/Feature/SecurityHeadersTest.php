<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_public_responses_include_baseline_security_headers(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_not_found_page_does_not_expose_debug_details_when_debug_is_disabled(): void
    {
        config()->set('app.debug', false);

        $response = $this->get('/route-that-does-not-exist');

        $response->assertNotFound()
            ->assertDontSee(base_path(), escape: false)
            ->assertDontSee('vendor/laravel/framework', escape: false);
    }
}
