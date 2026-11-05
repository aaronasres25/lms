<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['loggable_type', 'loggable_id', 'user_id', 'body'];

    public function user() { return $this->belongsTo(User::class); }
    public function loggable() { return $this->morphTo(); }
}
