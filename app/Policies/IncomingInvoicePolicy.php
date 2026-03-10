<?php

namespace App\Policies;

use App\Models\IncomingInvoice;
use App\Models\User;

class IncomingInvoicePolicy
{
    public function view(User $user, IncomingInvoice $incomingInvoice): bool
    {
        return $user->id === $incomingInvoice->user_id;
    }

    public function update(User $user, IncomingInvoice $incomingInvoice): bool
    {
        return $user->id === $incomingInvoice->user_id;
    }

    public function delete(User $user, IncomingInvoice $incomingInvoice): bool
    {
        return $user->id === $incomingInvoice->user_id;
    }
}
