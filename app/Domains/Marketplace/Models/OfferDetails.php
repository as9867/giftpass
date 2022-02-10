<?php

namespace App\Domains\Marketplace\Models;

use App\Domains\Marketplace\Models\Traits\Relationship\OfferdetailRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferDetails extends Model
{
    use HasFactory,
    OfferdetailRelationship;
    protected $table = 'offer_details';

    protected $fillable = [
        'offer_trade_id',
        'card_id',
    ];
}
