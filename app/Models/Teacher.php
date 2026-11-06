<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = ['user_id', 'employee_no', 'name', 'phone', 'email', 'qualification', 'joined_on', 'status'];
    protected $casts = ['joined_on' => 'date'];

    public function user() { return $this->belongsTo(User::class); }
    public function classSections() { return $this->hasMany(Section::class, 'class_teacher_id'); }
    public function assignments() { return $this->hasMany(SectionSubject::class); }

    /** Sections this teacher may take attendance for: as class teacher or as a subject teacher. */
    public function managedSectionIds(): array
    {
        return Section::where('class_teacher_id', $this->id)->pluck('id')
            ->merge(SectionSubject::where('teacher_id', $this->id)->pluck('section_id'))->unique()->values()->all();
    }
}
