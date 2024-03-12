<?php

namespace App\Models;

use App\Models\Mining;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMining extends Model
{
    use HasFactory;
    public function Mining()
    {
        return $this->belongsTo(Mining::class, 'mining_id');
    }
}
