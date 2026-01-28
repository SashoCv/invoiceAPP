<?php

namespace App\Policies;

use App\Models\ProformaInvoice;
use App\Models\User;

class ProformaInvoicePolicy
{
    public function view(User $user, ProformaInvoice $proformaInvoice): bool
    {
        return $user->id === $proformaInvoice->user_id;
    }

    public function update(User $user, ProformaInvoice $proformaInvoice): bool
    {
        return $user->id === $proformaInvoice->user_id;
    }

    public function delete(User $user, ProformaInvoice $proformaInvoice): bool
    {
        return $user->id === $proformaInvoice->user_id;
    }
}
