<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuthenticationLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_is_logged(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Clear any existing activities from user creation
        Activity::query()->delete();

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');

        // Vérifier que le log de connexion a été créé
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'authentication',
            'description' => 'Connexion réussie',
            'event' => 'login',
            'causer_id' => $user->id,
            'causer_type' => User::class,
        ]);

        $activity = Activity::where('log_name', 'authentication')
            ->where('event', 'login')
            ->where('causer_id', $user->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('ip_address', $activity->properties->toArray());
        $this->assertArrayHasKey('user_agent', $activity->properties->toArray());
    }

    public function test_failed_login_is_logged(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        // Clear any existing activities
        Activity::query()->delete();

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();

        // Vérifier que le log d'échec de connexion a été créé
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'authentication',
            'description' => 'Tentative de connexion échouée',
            'event' => 'failed_login',
        ]);

        $activity = Activity::where('log_name', 'authentication')
            ->where('event', 'failed_login')
            ->first();

        $this->assertNotNull($activity);
        $this->assertNull($activity->causer_id); // Anonymous
        $this->assertEquals('test@example.com', $activity->properties['email']);
        $this->assertArrayHasKey('ip_address', $activity->properties->toArray());
    }

    public function test_logout_is_logged(): void
    {
        $user = User::factory()->create();

        // Clear any existing activities
        Activity::query()->delete();

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');

        // Vérifier que le log de déconnexion a été créé
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'authentication',
            'description' => 'Déconnexion',
            'event' => 'logout',
            'causer_id' => $user->id,
            'causer_type' => User::class,
        ]);

        $activity = Activity::where('log_name', 'authentication')
            ->where('event', 'logout')
            ->where('causer_id', $user->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('ip_address', $activity->properties->toArray());
    }

    public function test_password_reset_is_logged(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Clear any existing activities
        Activity::query()->delete();

        // Simuler un password reset en déclenchant l'événement directement
        event(new \Illuminate\Auth\Events\PasswordReset($user));

        // Vérifier que le log de réinitialisation de mot de passe a été créé
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'authentication',
            'description' => 'Mot de passe réinitialisé',
            'event' => 'password_reset',
            'causer_id' => $user->id,
            'causer_type' => User::class,
        ]);

        $activity = Activity::where('log_name', 'authentication')
            ->where('event', 'password_reset')
            ->where('causer_id', $user->id)
            ->first();

        $this->assertNotNull($activity);
    }

    public function test_authentication_logs_contain_security_info(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Clear any existing activities
        Activity::query()->delete();

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $activity = Activity::where('log_name', 'authentication')
            ->where('event', 'login')
            ->first();

        // Vérifier que les informations de sécurité sont présentes
        $properties = $activity->properties->toArray();

        $this->assertArrayHasKey('ip_address', $properties);
        $this->assertArrayHasKey('user_agent', $properties);
        $this->assertArrayHasKey('guard', $properties);

        // Vérifier que l'IP n'est pas vide
        $this->assertNotEmpty($properties['ip_address']);
    }
}
