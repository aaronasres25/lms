<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** ir.sequence replacement. Sequence::next('loan') => 1, 2, 3 ... (race-safe). */
class Sequence extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['code', 'next_number'];

    public static function next(string $code): int
    {
        return DB::transaction(function () use ($code) {
            $row = static::query()->where('code', $code)->lockForUpdate()->first();
            if (! $row) {
                static::query()->insert(['code' => $code, 'next_number' => 1]);
                $row = static::query()->where('code', $code)->lockForUpdate()->first();
            }
            $n = (int) $row->next_number;
            static::query()->where('code', $code)->update(['next_number' => $n + 1]);
            return $n;
        });
    }
}
