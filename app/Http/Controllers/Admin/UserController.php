<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Users', [
            'users' => User::with('roles')->orderBy('name')->get(['id', 'name', 'email', 'created_at']),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $roles = Role::whereIn('id', $data['roles'] ?? [])->pluck('name');
        $user->syncRoles($roles);

        return back()->with('success', 'User roles updated.');
    }
}
