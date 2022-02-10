<?php

namespace App\Domains\Marketplace\Models\Traits\Relationship;

use App\Domains\Activity\Models\Activity;
use App\Domains\Auth\Models\User;
use App\Domains\Marketplace\Models\Bidding;
use App\Domains\Marketplace\Models\Marketplacecards;
use App\Domains\Marketplace\Models\OfferTrades;

trait MarketplaceRelationship
{
    public function cards()
    {
        return $this->hasMany(Marketplacecards::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }

    public function offer_trades()
    {
        return $this->hasMany(OfferTrades::class);
    }

    public function biddings()
    {
        return $this->hasMany(Bidding::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
