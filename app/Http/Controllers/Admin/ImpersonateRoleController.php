<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ImpersonateRoleController extends Controller
{
    public function store(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'Super Admin', 422, 'Cannot impersonate the Super Admin role.');

        $request->session()->put('impersonating_role_id', $role->id);

        return redirect()->intended(route('dashboard'))
            ->with('success', "Now impersonating: {$role->name}");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('impersonating_role_id');

        return redirect()->intended(route('admin.roles.index'))
            ->with('success', 'Stopped impersonating.');
    }
}
