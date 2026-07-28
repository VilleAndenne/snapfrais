<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'organization_name',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function forms(): HasMany
    {
        return $this->hasMany(Form::class);
    }

    public function expenseSheets(): HasMany
    {
        return $this->hasMany(ExpenseSheet::class);
    }

    /**
     * Base URL of the organization: its own `domain` if set, otherwise the
     * `{slug}.{APP_URL host}` subdomain convention. Used to build absolute
     * links in queued notifications, which run without a request context.
     */
    public function baseUrl(): string
    {
        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';

        if (! empty($this->domain)) {
            return $scheme.'://'.$this->domain;
        }

        $appHost = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        $port = parse_url($appUrl, PHP_URL_PORT);

        return $scheme.'://'.$this->slug.'.'.$appHost.($port ? ':'.$port : '');
    }

    /**
     * Build an absolute URL on the organization's own host.
     */
    public function url(string $path = ''): string
    {
        return rtrim($this->baseUrl(), '/').'/'.ltrim($path, '/');
    }

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'domain', 'organization_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('organization')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Organisation créée',
                'updated' => 'Organisation modifiée',
                'deleted' => 'Organisation supprimée',
                default => "Organisation {$eventName}",
            });
    }
}
