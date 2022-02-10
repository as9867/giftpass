<?php

namespace App\Domains\Card\Models\Traits\Relationship;

use App\Domains\Card\Models\Brand;

trait CardRelationship
{
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
