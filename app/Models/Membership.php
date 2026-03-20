<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \App\Models\User;
use \App\Models\Store;

class Membership extends Model
{
    protected $fillable = ['user_id', 'store_id', 'role'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
