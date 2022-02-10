<?php

namespace App\Domains\Marketplace\Models;

use App\Domains\Marketplace\Models\Traits\Relationship\BiddingRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bidding extends Model
{
    use HasFactory,
    BiddingRelationship;
    protected $table = 'biddings';

    protected $fillable = [
        'marketplace_id',
        'bidding_amount',
        'user_id',
        'active',
        'withdraw_message',
        'withdraw_datetime',
        'admin_reason'
    ];

    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('active',1);
        });
    }
}
