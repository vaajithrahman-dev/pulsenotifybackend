<?php

namespace App\Models;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot(['role'])
            ->withTimestamps();
    }
}
