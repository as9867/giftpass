<?php

namespace App\Domains\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domains\Marketplace\Models\Traits\Attributes\MarketplaceAttribute;
use App\Domains\Marketplace\Models\Traits\Relationship\MarketplaceRelationship;

class Marketplace extends Model
{
    use HasFactory,
        MarketplaceAttribute,
        MarketplaceRelationship;

    protected $table = 'marketplace';

    protected $guarded = [];

    protected $dates = ['bidding_expiry'];

    protected static function booted()
    {
        static::addGlobalScope('listing_type', function (Builder $builder) {
            $builder->where('listing_type', '!=', 'auction');
        });
    }
}
