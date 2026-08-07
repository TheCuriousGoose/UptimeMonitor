<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        $allowed = $role instanceof Role
            ? $this->user()->can('update', $role)
            : $this->user()->can('create', Role::class);

        return $allowed && $this->grantsOnlyHeldPermissions();
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role instanceof Role ? $role->id : null),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function permissionNames(): array
    {
        return $this->requestedPermissions()->all();
    }

    private function grantsOnlyHeldPermissions(): bool
    {
        return $this->requestedPermissions()->every(
            fn (string $name) => $this->user()->can('grantPermission', [Role::class, $name]),
        );
    }

    /**
     * @return Collection<int, string>
     */
    private function requestedPermissions(): Collection
    {
        return once(fn () => Permission::query()
            ->whereIn('id', (array) $this->input('permissions', []))
            ->pluck('name'));
    }
}
