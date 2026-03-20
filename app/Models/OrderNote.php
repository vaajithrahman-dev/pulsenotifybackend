<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderNote extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'note',
        'customer_note',
        'actor_user_id',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
