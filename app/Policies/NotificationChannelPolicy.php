<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOwnership;

class NotificationChannelPolicy
{
    use AuthorizesOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ChannelsView->value);
    }

    public function view(User $user, NotificationChannel $channel): bool
    {
        return $this->owns($user, $channel->user_id, Permission::ChannelsView);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ChannelsCreate->value);
    }

    public function update(User $user, NotificationChannel $channel): bool
    {
        return $this->owns($user, $channel->user_id, Permission::ChannelsEdit);
    }

    public function delete(User $user, NotificationChannel $channel): bool
    {
        return $this->owns($user, $channel->user_id, Permission::ChannelsDelete);
    }
}
