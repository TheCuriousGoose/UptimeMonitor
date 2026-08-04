<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), fn ($q) => $q
                ->where('name', 'like', "%{$request->input('search')}%")
                ->orWhere('email', 'like', "%{$request->input('search')}%")
            )
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('admin/Users', [
            'users' => $users->toResourceCollection(),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $roles = Role::whereIn('id', $data['roles'] ?? [])->pluck('name');
        $user->syncRoles($roles);

        Inertia::flash('toast', ['type' => 'success', 'message' => "User \"{$user->name}\" updated."]);

        return back();
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::default()],
        ]);

        $user->update(['password' => $data['password']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Password updated for \"{$user->name}\"."]);

        return back();
    }

    public function sendPasswordResetLink(User $user): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        Inertia::flash('toast', [
            'type' => $status === Password::RESET_LINK_SENT ? 'success' : 'error',
            'message' => __($status),
        ]);

        return back();
    }
}
