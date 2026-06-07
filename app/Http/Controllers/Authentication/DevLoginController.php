<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DevLoginController extends Controller
{
    /**
     * Quickly login as a Super Admin (development only).
     */
    public function loginAsAdmin(): RedirectResponse
    {
        if (!app()->isLocal()) {
            abort(403, 'This action is only available in local development.');
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.test'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (!$admin->hasRole('Super Admin')) {
            $admin->syncRoles('Super Admin');
        }

        Auth::login($admin, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Quickly login as a regular user (development only).
     */
    public function loginAsUser(): RedirectResponse
    {
        if (!app()->isLocal()) {
            abort(403, 'This action is only available in local development.');
        }

        $user = User::firstOrCreate(
            ['email' => 'user@example.test'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
