<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;

class ExpenseCategoryPolicy
{
    public function view(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->id === $expenseCategory->user_id;
    }

    public function update(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->id === $expenseCategory->user_id;
    }

    public function delete(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->id === $expenseCategory->user_id;
    }
}
