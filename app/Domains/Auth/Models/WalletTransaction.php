<?php

namespace App\Domains\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;
    protected $table = 'wallets_transaction';

    protected $fillable = [
        'activity_id',
        'marketplace_id',
        'transaction_type',
        'amount',
        'from_user',
        'to_user',
        'status',
    ];
}
