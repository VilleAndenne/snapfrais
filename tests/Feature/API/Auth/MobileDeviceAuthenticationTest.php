<?php

namespace Tests\Feature\API\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileDeviceAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_via_api_without_push_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user',
            ]);

        $this->assertDatabaseCount('mobile_devices', 0);
    }

    public function test_user_can_login_via_api_with_push_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $pushToken = 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]';

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'push_token' => $pushToken,
            'platform' => 'ios',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user',
            ]);

        $this->assertDatabaseHas('mobile_devices', [
            'user_id' => $user->id,
            'token' => $pushToken,
            'platform' => 'ios',
        ]);
    }

    public function test_push_token_is_updated_on_subsequent_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $firstPushToken = 'ExponentPushToken[firsttoken]';
        $secondPushToken = 'ExponentPushToken[secondtoken]';

        $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'push_token' => $firstPushToken,
            'platform' => 'ios',
        ]);

        $this->assertDatabaseHas('mobile_devices', [
            'user_id' => $user->id,
            'token' => $firstPushToken,
            'platform' => 'ios',
        ]);

        $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'push_token' => $firstPushToken,
            'platform' => 'android',
        ]);

        $this->assertDatabaseHas('mobile_devices', [
            'user_id' => $user->id,
            'token' => $firstPushToken,
            'platform' => 'android',
        ]);

        $this->assertDatabaseCount('mobile_devices', 1);
    }

    public function test_multiple_devices_can_be_registered_for_one_user(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $iosToken = 'ExponentPushToken[iostoken]';
        $androidToken = 'ExponentPushToken[androidtoken]';

        $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'push_token' => $iosToken,
            'platform' => 'ios',
        ]);

        $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'push_token' => $androidToken,
            'platform' => 'android',
        ]);

        $this->assertDatabaseHas('mobile_devices', [
            'user_id' => $user->id,
            'token' => $iosToken,
            'platform' => 'ios',
        ]);

        $this->assertDatabaseHas('mobile_devices', [
            'user_id' => $user->id,
            'token' => $androidToken,
            'platform' => 'android',
        ]);

        $this->assertDatabaseCount('mobile_devices', 2);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            'push_token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
            'platform' => 'ios',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('mobile_devices', 0);
    }

    public function test_platform_validation_only_allows_ios_and_android(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'push_token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
            'platform' => 'windows',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform']);
    }
}
