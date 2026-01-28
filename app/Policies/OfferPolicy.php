<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;

class OfferPolicy
{
    public function view(User $user, Offer $offer): bool
    {
        return $user->id === $offer->user_id;
    }

    public function update(User $user, Offer $offer): bool
    {
        return $user->id === $offer->user_id;
    }

    public function delete(User $user, Offer $offer): bool
    {
        return $user->id === $offer->user_id;
    }
}
