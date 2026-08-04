<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\NotificationChannel;
use App\Models\User;

class NotificationChannelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ChannelsView->value);
    }

    public function view(User $user, NotificationChannel $channel): bool
    {
        return $channel->user_id === $user->id
            && $user->can(Permission::ChannelsView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ChannelsCreate->value);
    }

    public function update(User $user, NotificationChannel $channel): bool
    {
        return $channel->user_id === $user->id
            && $user->can(Permission::ChannelsEdit->value);
    }

    public function delete(User $user, NotificationChannel $channel): bool
    {
        return $channel->user_id === $user->id
            && $user->can(Permission::ChannelsDelete->value);
    }
}
