<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRight extends Model
{
    protected $fillable = ['user_id', 'resource', 'can_read', 'can_create', 'can_update', 'can_delete'];
    protected $casts = ['can_read' => 'boolean', 'can_create' => 'boolean', 'can_update' => 'boolean', 'can_delete' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
}
