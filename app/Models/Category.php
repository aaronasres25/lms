<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'description', 'parent_id'];

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function books() { return $this->hasMany(Book::class); }

    public function descendantIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];
        while ($frontier) {
            $frontier = self::whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }
        return $ids;
    }
}
