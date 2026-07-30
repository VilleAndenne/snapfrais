<?php

namespace App\Http\Middleware;

use App\Models\Department;
use App\Models\Form;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $organization = currentOrganization();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'organization' => $organization === null ? null : [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'organizationName' => $organization->organization_name,
            ],
            'organizationSwitcher' => $this->organizationSwitcher($request->user(), $organization),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'can' => [
                    'departments' => $request->user()?->can('viewAny', Department::class),
                    'users' => $request->user()?->can('viewAny', User::class),
                    'forms' => $request->user()?->can('viewAny', Form::class),
                ],
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => fn () => session()->get('success'),
                'error' => fn () => session()->get('error'),
                'warning' => fn () => session()->get('warning'),
                'info' => fn () => session()->get('info'),
            ],
        ];
    }

    /**
     * Organization switcher payload shared with admins, letting them change the
     * active organization without changing the URL. A super_admin gets every
     * organization; a platform admin only their own. Null for everyone else.
     *
     * @return array{current: int|null, options: list<array{id: int, organizationName: string}>}|null
     */
    protected function organizationSwitcher(?User $user, ?Organization $organization): ?array
    {
        if ($user === null || ! $user->canSwitchOrganizations()) {
            return null;
        }

        $options = $user->switchableOrganizations()
            ->map(fn (Organization $org): array => [
                'id' => $org->id,
                'organizationName' => $org->organization_name,
            ])
            ->values()
            ->all();

        if (count($options) < 2) {
            return null;
        }

        return [
            'current' => $organization?->id,
            'options' => $options,
        ];
    }
}
