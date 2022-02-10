<?php

namespace App\Domains\Marketplace\Models;

use App\Domains\Marketplace\Models\Traits\Relationship\MarketplacecardsRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marketplacecards extends Model
{
    use HasFactory,
    MarketplacecardsRelationship;

    protected $table = 'marketplace_cards';

    protected $guarded = [];

}
