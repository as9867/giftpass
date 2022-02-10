<?php

namespace App\Domains\Marketplace\Models\Traits\Attributes;

use App\Domains\Auth\Models\User;

trait MarketplaceAttribute
{
    public function getNumberOfCardsAttribute()
    {
        return $this->cards()->count();
    }

    public function getCardBrandsAttribute()
    {
        // return $this->cards->map(fn ($card) => $card->card()->withoutGlobalScope('active')->first()->brand->name)->implode(', ');
        return $this->cards->map(fn ($card) => $card->card()->withoutGlobalScope('active')->first()->brand()->withoutGlobalScope('active')->first()->name)->implode(', ');
    }

    // public function getUserAttribute()
    // {
    //     return User::find($this->user_id);
    // }

    public function getStatusAttribute($value)
    {
        // Use Laravel Enum
        return $value;
    }
}
