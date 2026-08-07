<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'))
            && $this->assignsOnlyPermittedRoles();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')->id),
            ],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ];
    }

    /**
     * @return Collection<int, string>
     */
    public function roleNames(): Collection
    {
        return $this->requestedRoles();
    }

    private function assignsOnlyPermittedRoles(): bool
    {
        return $this->requestedRoles()->every(function (string $name) {
            $role = RoleEnum::tryFrom($name);

            return $role === null || $this->user()->can('assignRole', [User::class, $role]);
        });
    }

    /**
     * @return Collection<int, string>
     */
    private function requestedRoles(): Collection
    {
        return once(fn () => Role::query()
            ->whereIn('id', (array) $this->input('roles', []))
            ->pluck('name'));
    }
}
