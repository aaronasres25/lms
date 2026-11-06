<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['grade_level_id', 'academic_year_id', 'name', 'class_teacher_id', 'capacity', 'room'];

    public function gradeLevel() { return $this->belongsTo(GradeLevel::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function classTeacher() { return $this->belongsTo(Teacher::class, 'class_teacher_id'); }
    public function students() { return $this->hasMany(Student::class); }
    public function assignments() { return $this->hasMany(SectionSubject::class); }
    public function slots() { return $this->hasMany(TimetableSlot::class); }

    /** "Grade 5 - A" */
    public function getLabelAttribute(): string
    {
        return trim(($this->gradeLevel->name ?? '') . ' - ' . $this->name, ' -');
    }
}
