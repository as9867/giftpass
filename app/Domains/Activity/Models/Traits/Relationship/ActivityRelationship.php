<?php

namespace App\Domains\Activity\Models\Traits\Relationship;

use App\Domains\Auth\Models\User;
use App\Domains\Card\Models\Brand;

trait ActivityRelationship
{
    public function performer()
    {
        return $this->belongsTo(User::class, 'action_user_id', 'id');
    }

    public function offered_card()
    {
        return $this->belongsTo(Brand::class, 'offered_brand_id', 'id');
    }

    public function brand(){
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }
}
