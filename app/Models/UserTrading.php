<?php

namespace App\Models;

use App\Models\UserTrading;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTrading extends Model
{
    use HasFactory;
    //fillable
    protected $fillable = [
        'is_profit',
        'is_commission',
    ];
    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}