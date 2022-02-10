<?php

namespace App\Domains\Marketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    use HasFactory;
    protected $table = 'gifts';

    protected $fillable = [
        'user_id',
        'method_of_delivery',
        'recipient',
        'message',
        'date_of_delivery',
        'card_id',
        'sender_user_id'
    ];
}
