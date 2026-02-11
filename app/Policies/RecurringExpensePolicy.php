<?php

namespace App\Policies;

use App\Models\RecurringExpense;
use App\Models\User;

class RecurringExpensePolicy
{
    public function view(User $user, RecurringExpense $recurringExpense): bool
    {
        return $user->id === $recurringExpense->user_id;
    }

    public function update(User $user, RecurringExpense $recurringExpense): bool
    {
        return $user->id === $recurringExpense->user_id;
    }

    public function delete(User $user, RecurringExpense $recurringExpense): bool
    {
        return $user->id === $recurringExpense->user_id;
    }
}
