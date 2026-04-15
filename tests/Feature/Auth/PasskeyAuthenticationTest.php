<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasskeyAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_options_endpoint_is_accessible_to_guests(): void
    {
        $this->get('/passkeys/authentication-options')->assertOk();
    }

    public function test_authenticate_endpoint_requires_start_authentication_response(): void
    {
        $this->post('/passkeys/authenticate', [])
            ->assertSessionHasErrors('start_authentication_response');
    }

    public function test_authenticate_endpoint_requires_json_payload(): void
    {
        $this->post('/passkeys/authenticate', [
            'start_authentication_response' => 'not-json',
        ])->assertSessionHasErrors('start_authentication_response');
    }

    public function test_authentication_options_endpoint_stores_options_in_session(): void
    {
        $this->get('/passkeys/authentication-options')
            ->assertOk()
            ->assertSessionHas('passkey-authentication-options');
    }
}
