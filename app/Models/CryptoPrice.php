<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoPrice extends Model
{
    use HasFactory;
    protected $fillable = [
        'open',
        'high',
        'low',
        'close',
        'trading_crypto',
        'trading_fiat',
        'volume',
    ];

}
