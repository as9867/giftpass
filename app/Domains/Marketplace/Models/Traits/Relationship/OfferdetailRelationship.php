<?php

namespace App\Domains\Marketplace\Models\Traits\Relationship;

use App\Domains\Auth\Models\User;
use App\Domains\Card\Models\Brand;
use App\Domains\Card\Models\Card;
use App\Domains\Marketplace\Models\Marketplacecards;
use App\Domains\Marketplace\Models\OfferDetails;

trait OfferdetailRelationship
{

    public function card(){
        return $this->belongsTo(Card::class,'card_id','id');
    }
  
}
