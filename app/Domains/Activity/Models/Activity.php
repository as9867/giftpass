<?php

namespace App\Domains\Activity\Models;

use App\Domains\Activity\Models\Traits\Relationship\ActivityRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory,
        ActivityRelationship;

    protected $table = 'user_activity';

    protected $guarded = [];
}
