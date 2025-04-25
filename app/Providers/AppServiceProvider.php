<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Policies\DepartmentPolicy;
use App\Policies\ExpenseSheetPolicy;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ExpenseSheet::class, ExpenseSheetPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);

        if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage())
                ->subject('Vérifiez votre adresse email')
                ->greeting('Bonjour,')
                ->line('Veuillez cliquer sur le bouton ci-dessous pour vérifier votre adresse email.')
                ->action('Vérifier mon adresse email', $url)
                ->salutation('Cordialement,');
        });
    }
}
