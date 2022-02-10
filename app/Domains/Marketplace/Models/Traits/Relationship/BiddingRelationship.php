<?php

namespace App\Domains\Marketplace\Models\Traits\Relationship;

use App\Domains\Auth\Models\User;
use App\Domains\Card\Models\Brand;
use App\Domains\Card\Models\Card;
use App\Domains\Marketplace\Models\Marketplacecards;
use App\Domains\Marketplace\Models\OfferDetails;

trait BiddingRelationship
{
    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }
}
