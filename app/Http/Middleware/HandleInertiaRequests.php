<?php

namespace App\Http\Middleware;

use App\Settings\SettingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

    private ?SettingRepository $settingRepository = null;

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
        $this->settingRepository = new SettingRepository;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => app()->getLocale(),
            'supportUrl' => config('app.support_url') ?: null,
            'auth' => [
                'is_authenticated' => $request->user() !== null,
                'user' => $request->user(),
                'roles' => $request->user()?->getRoleNames() ?? [],
                'permissions' => $this->getUserPermissions($request),
                'impersonating_role' => $request->session()->has('impersonating_role_id')
                    ? Role::find($request->session()->get('impersonating_role_id'))?->name
                    : null,
            ],
            'settings' => [
                'authentication' => $this->settingRepository->authenticationSettings(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    // Super Admin bypasses the permission table (see User::can()), so it has
    // no rows of its own to report.
    private function getUserPermissions(Request $request): Collection
    {
        return once(function () use ($request) {
            $user = $request->user();

            if ($user === null) {
                return collect();
            }

            return $user->hasRole('Super Admin')
                ? Permission::all()->pluck('name')
                : $user->getAllPermissions()->pluck('name');
        });
    }
}
