<?php

namespace App\Policies;

use App\Models\Bundle;
use App\Models\User;

class BundlePolicy
{
    public function view(User $user, Bundle $bundle): bool
    {
        return $user->id === $bundle->user_id;
    }

    public function update(User $user, Bundle $bundle): bool
    {
        return $user->id === $bundle->user_id;
    }

    public function delete(User $user, Bundle $bundle): bool
    {
        return $user->id === $bundle->user_id;
    }
}
