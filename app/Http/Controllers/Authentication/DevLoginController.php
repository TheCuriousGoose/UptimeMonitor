<?php

namespace App\Http\Controllers\Authentication;

use App\Actions\Users\CreateUser;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DevLoginController extends Controller
{
    public function __construct(private readonly CreateUser $createUser) {}

    /**
     * Quickly login as a Super Admin (development only).
     */
    public function loginAsAdmin(): RedirectResponse
    {
        if (! app()->isLocal()) {
            abort(403, 'This action is only available in local development.');
        }

        $admin = $this->createUser->firstOrCreate(
            name: 'Admin User',
            email: 'admin@example.test',
            password: 'password',
            role: Role::SuperAdmin,
            verified: true,
        );

        $admin->syncRoles(Role::SuperAdmin->value);

        Auth::login($admin, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Quickly login as a regular user (development only).
     */
    public function loginAsUser(): RedirectResponse
    {
        if (! app()->isLocal()) {
            abort(403, 'This action is only available in local development.');
        }

        $user = $this->createUser->firstOrCreate(
            name: 'Test User',
            email: 'user@example.test',
            password: 'password',
            role: Role::User,
            verified: true,
        );

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
