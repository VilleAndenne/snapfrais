<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPasskeys\Models\Passkey;
use Tests\TestCase;

class PasskeyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_passkeys_page_is_displayed_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings/passkeys')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Passkeys')
                ->has('passkeys')
            );
    }

    public function test_passkeys_page_requires_authentication(): void
    {
        $this->get('/settings/passkeys')->assertRedirect('/login');
    }

    public function test_registration_options_endpoint_returns_json_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings/passkeys/options');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $this->assertJson($response->getContent());
    }

    public function test_registration_options_endpoint_requires_authentication(): void
    {
        $this->get('/settings/passkeys/options')->assertRedirect('/login');
    }

    public function test_user_can_delete_their_own_passkey(): void
    {
        $user = User::factory()->create();
        $passkey = Passkey::factory()->create([
            'authenticatable_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete("/settings/passkeys/{$passkey->id}")
            ->assertRedirect(route('passkeys.edit'));

        $this->assertDatabaseMissing('passkeys', ['id' => $passkey->id]);
    }

    public function test_user_cannot_delete_another_users_passkey(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $passkey = Passkey::factory()->create([
            'authenticatable_id' => $otherUser->id,
        ]);

        $this->actingAs($user)
            ->delete("/settings/passkeys/{$passkey->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
    }

    public function test_store_rejects_payload_without_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/passkeys', [
                'passkey' => json_encode(['dummy' => true]),
                'options' => json_encode(['dummy' => true]),
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_store_rejects_non_json_passkey_payload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/passkeys', [
                'name' => 'MacBook',
                'passkey' => 'not-json',
                'options' => json_encode(['dummy' => true]),
            ])
            ->assertSessionHasErrors('passkey');
    }
}
