<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'audience', 'is_published', 'published_by'];
    protected $casts = ['is_published' => 'boolean'];

    /** Published notices visible to a given audience ('students' | 'teachers'). */
    public function scopeFor($q, string $audience)
    {
        return $q->where('is_published', true)->whereIn('audience', ['all', $audience]);
    }
}
