<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrLogin extends Model
{
    protected $fillable = [
        'store_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];
}
