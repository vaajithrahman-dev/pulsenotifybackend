<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
        public $timestamps = false; // because audit_logs has only created_at (no updated_at)

    protected $fillable = [
        'store_id',
        'actor_user_id',
        'action',
        'context_json',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
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
