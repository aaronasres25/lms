<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'parent_id', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function books() { return $this->hasMany(Book::class); }

    /** "ICT / System Development" */
    public function getCompleteNameAttribute(): string
    {
        $parts = [$this->name];
        $node = $this;
        $guard = 0;
        while ($node->parent_id && $guard++ < 20) {
            $node = $node->parent ?? self::find($node->parent_id);
            if (! $node) break;
            array_unshift($parts, $node->name);
        }
        return implode(' / ', $parts);
    }

    /** This department + all descendants (Odoo "child_of"). */
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

    /** Books in this department tree (active only), like the stored recursive book_count. */
    public function getBookCountAttribute(): int
    {
        return Book::whereIn('department_id', $this->descendantIds())->where('active', true)->count();
    }

    /** [id => "Parent / Child"] built in memory (no N+1) - for selects and grouping. */
    public static function options(): array
    {
        $all = self::orderBy('name')->get(['id', 'name', 'parent_id'])->keyBy('id');
        $name = function ($d) use ($all) {
            $parts = [$d->name]; $guard = 0;
            while ($d->parent_id && isset($all[$d->parent_id]) && $guard++ < 20) {
                $d = $all[$d->parent_id];
                array_unshift($parts, $d->name);
            }
            return implode(' / ', $parts);
        };
        $out = [];
        foreach ($all as $d) { $out[$d->id] = $name($d); }
        asort($out);
        return $out;
    }

    /** Port of name_create: "ICT / Infrastructure" finds-or-creates the whole path. */
    public static function findOrCreatePath(string $path): self
    {
        $parent = null;
        foreach (array_map('trim', explode('/', $path)) as $part) {
            if ($part === '') continue;
            $parent = self::firstOrCreate(['name' => $part, 'parent_id' => $parent?->id], ['active' => true]);
        }
        return $parent;
    }
}
