<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeScale extends Model
{
    protected $fillable = ['name', 'min_percent', 'remark'];
    protected $casts = ['min_percent' => 'float'];

    private static ?\Illuminate\Support\Collection $cache = null;

    /** @return array{0:string,1:string} [letter, remark] for a percentage */
    public static function gradeFor(?float $percent): array
    {
        if ($percent === null) return ['-', ''];
        self::$cache ??= static::orderByDesc('min_percent')->get();
        foreach (self::$cache as $g) {
            if ($percent + 1e-9 >= $g->min_percent) return [$g->name, (string) $g->remark];
        }
        $last = self::$cache->last();
        return $last ? [$last->name, (string) $last->remark] : ['-', ''];
    }
}
