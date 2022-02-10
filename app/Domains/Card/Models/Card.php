<?php

namespace App\Domains\Card\Models;

use App\Domains\Card\Models\Traits\Relationship\CardRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\EncryptableDbAttributes;

class Card extends Model
{
    use HasFactory,
        CardRelationship,
        EncryptableDbAttributes;

    // protected $guarded = [];
    protected $encryptable = [
        'secret',
        'srno',
        'url'
    ];

    protected $hidden = [
        'secret',
        'srno',
        'url'
    ];

    protected $fillable = [
        'brand_id',
        'value',
        'secret',
        'expiry',
        'user_id',
        'srno',
        'url'
    ];

    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('active', '=','1');
        });
    }
}
