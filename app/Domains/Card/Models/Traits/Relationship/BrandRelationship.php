<?php

namespace App\Domains\Card\Models\Traits\Relationship;

use App\Domains\Card\Models\Brand;
use App\Domains\Card\Models\Categories;

trait BrandRelationship
{
    public function category()
    {
        return $this->belongsTo(Categories::class,'category_id','id');
    }
}
