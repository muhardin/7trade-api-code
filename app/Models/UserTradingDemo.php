<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTradingDemo extends Model
{
    use HasFactory;
    protected $fillable = [
        'is_profit',
        'is_commission',
    ];
    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
