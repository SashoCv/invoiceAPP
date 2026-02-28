<?php

namespace App\Policies;

use App\Models\BankTransaction;
use App\Models\User;

class BankTransactionPolicy
{
    public function view(User $user, BankTransaction $bankTransaction): bool
    {
        return $user->id === $bankTransaction->user_id;
    }

    public function update(User $user, BankTransaction $bankTransaction): bool
    {
        return $user->id === $bankTransaction->user_id;
    }

    public function delete(User $user, BankTransaction $bankTransaction): bool
    {
        return $user->id === $bankTransaction->user_id;
    }
}
