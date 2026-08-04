<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\StatusPage;
use App\Models\User;

class StatusPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::StatusPagesView->value);
    }

    public function view(User $user, StatusPage $page): bool
    {
        return $page->user_id === $user->id
            && $user->can(Permission::StatusPagesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::StatusPagesCreate->value);
    }

    public function update(User $user, StatusPage $page): bool
    {
        return $page->user_id === $user->id
            && $user->can(Permission::StatusPagesEdit->value);
    }

    public function delete(User $user, StatusPage $page): bool
    {
        return $page->user_id === $user->id
            && $user->can(Permission::StatusPagesDelete->value);
    }
}
