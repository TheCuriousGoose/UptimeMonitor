<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\StatusPage;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOwnership;

class StatusPagePolicy
{
    use AuthorizesOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::StatusPagesView->value);
    }

    public function view(User $user, StatusPage $page): bool
    {
        return $this->owns($user, $page->user_id, Permission::StatusPagesView);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::StatusPagesCreate->value);
    }

    public function update(User $user, StatusPage $page): bool
    {
        return $this->owns($user, $page->user_id, Permission::StatusPagesEdit);
    }

    public function delete(User $user, StatusPage $page): bool
    {
        return $this->owns($user, $page->user_id, Permission::StatusPagesDelete);
    }
}
