<?php

namespace App\Domains\Marketplace\Models\Traits\Relationship;

use App\Domains\Card\Models\Brand;
use App\Domains\Card\Models\Card;
use App\Domains\Marketplace\Models\Marketplacecards;

trait MarketplacecardsRelationship
{
    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function trading_brand(){
        return $this->belongsTo(Brand::class,'trading_brand_id','id');
    }
}
