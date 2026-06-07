<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class HandleRoleImpersonation
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && $request->session()->has('impersonating_role_id')) {
            $role = Role::find($request->session()->get('impersonating_role_id'));

            if ($role) {
                Auth::user()->setRelation('roles', collect([$role]));
                Auth::user()->setRelation('permissions', collect());
            } else {
                $request->session()->forget('impersonating_role_id');
            }
        }

        return $next($request);
    }
}
