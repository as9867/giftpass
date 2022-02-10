<?php

namespace App\Domains\Marketplace\Models\Traits\Attributes;

trait OfferTradeAttribute
{
    public function getTradeCardsAttribute()
    {
        return $this->offer_details->map(fn ($detail) => $detail->card->brand->name)->implode(', ');
    }
}
