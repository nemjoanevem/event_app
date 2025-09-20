<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;
use App\Models\Event;

class EventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        if ($event->status === 'published') {
            return true;
        }

        return $user->isAdmin() || ($user->isOrganizer() && $event->created_by === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer() || $user->isAdmin();
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->isOrganizer() && $event->created_by === $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->isOrganizer() && $event->created_by === $user->id;
    }

    public function changeStatus(User $user, Event $event): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->isOrganizer() && $event->created_by === $user->id;
    }
}
