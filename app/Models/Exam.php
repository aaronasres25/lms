<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = ['academic_year_id', 'term_id', 'name', 'start_date', 'end_date', 'max_marks', 'is_published'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'is_published' => 'boolean', 'max_marks' => 'integer'];

    public function year() { return $this->belongsTo(AcademicYear::class, 'academic_year_id'); }
    public function term() { return $this->belongsTo(Term::class); }
    public function marks() { return $this->hasMany(ExamMark::class); }
}
