<?php

namespace App\Domains\Marketplace\Models;

use App\Domains\Marketplace\Models\Traits\Attributes\OfferTradeAttribute;
use App\Domains\Marketplace\Models\Traits\Relationship\OffertradeRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferTrades extends Model
{
    use HasFactory,
        OffertradeRelationship,
        OfferTradeAttribute;

    protected $table = 'offer_trades';

    protected $fillable = [
        'marketplace_id',
        'user_id_of_offer',
        'status',
    ];
}
