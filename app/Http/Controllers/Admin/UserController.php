<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Support\SqlDialect;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

#[UsePolicy(UserPolicy::class)]
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), fn ($q) => $q
                ->where('name', SqlDialect::like(), "%{$request->input('search')}%")
                ->orWhere('email', SqlDialect::like(), "%{$request->input('search')}%")
            )
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('admin/Users', [
            'users' => $users->toResourceCollection(),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $user->syncRoles($request->roleNames());

        Inertia::flash('toast', ['type' => 'success', 'message' => "User \"{$user->name}\" updated."]);

        return back();
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $data = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::default()],
        ]);

        $user->update(['password' => $data['password']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Password updated for \"{$user->name}\"."]);

        return back();
    }

    public function sendPasswordResetLink(User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $status = Password::sendResetLink(['email' => $user->email]);

        Inertia::flash('toast', [
            'type' => $status === Password::RESET_LINK_SENT ? 'success' : 'error',
            'message' => __($status),
        ]);

        return back();
    }
}
