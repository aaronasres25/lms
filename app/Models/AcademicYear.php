<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'is_current'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'is_current' => 'boolean'];

    public function terms() { return $this->hasMany(Term::class); }
    public function sections() { return $this->hasMany(Section::class); }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first() ?? static::orderByDesc('start_date')->first();
    }
}
