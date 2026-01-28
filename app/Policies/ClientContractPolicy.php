<?php

namespace App\Policies;

use App\Models\ClientContract;
use App\Models\User;

class ClientContractPolicy
{
    public function view(User $user, ClientContract $contract): bool
    {
        return $user->id === $contract->user_id;
    }

    public function delete(User $user, ClientContract $contract): bool
    {
        return $user->id === $contract->user_id;
    }
}
