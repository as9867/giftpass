<?php

namespace App\Domains\Auth\Models\Traits\Relationship;

use App\Domains\Auth\Models\PasswordHistory;
use App\Domains\Card\Models\Card;

/**
 * Class UserRelationship.
 */
trait UserRelationship
{
    /**
     * @return mixed
     */
    public function passwordHistories()
    {
        return $this->morphMany(PasswordHistory::class, 'model');
    }
    public function cards(){
        return $this->hasMany(Card::class);
    }
}
