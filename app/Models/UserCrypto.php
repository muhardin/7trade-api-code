<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCrypto extends Model
{
    use HasFactory;
    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
