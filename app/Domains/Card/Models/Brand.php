<?php

namespace App\Domains\Card\Models;

use App\Domains\Card\Models\Traits\Relationship\BrandRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Brand extends Model
{
    use HasFactory,BrandRelationship;

    protected $guarded = [];
    protected $dates = [];

    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('active',1);
        });
    }

    public function getLogoAttribute($value){
        return asset($value);
    }
}
