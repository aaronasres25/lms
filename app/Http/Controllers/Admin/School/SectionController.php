<?php

namespace App\Http\Controllers\Admin\School;

use App\Exceptions\LmsException;
use App\Http\Controllers\Admin\MasterController;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Classes ("Grade 5 - A" in one academic year) + their subject / teacher assignments. */
class SectionController extends MasterController
{
    protected function modelClass(): string { return Section::class; }
    protected function title(): string { return 'Class'; }
    protected function routePrefix(): string { return 'admin.school.sections'; }
    protected function permResource(): string { return 'academics'; }
    protected function orderColumn(): string { return 'grade_level_id'; }
    protected function listQuery($query) { return $query->with(['gradeLevel', 'academicYear', 'classTeacher'])->withCount('students'); }

    protected function fields(): array
    {
        return [
            ['name' => 'grade_level_id', 'label' => 'Grade level', 'type' => 'select', 'required' => true, 'options' => GradeLevel::orderBy('level_order')->pluck('name', 'id')->all()],
            ['name' => 'name', 'label' => 'Section (A, B…)', 'type' => 'text', 'required' => true],
            ['name' => 'academic_year_id', 'label' => 'Academic year', 'type' => 'select', 'required' => true, 'options' => AcademicYear::orderByDesc('start_date')->pluck('name', 'id')->all()],
            ['name' => 'class_teacher_id', 'label' => 'Class teacher', 'type' => 'select', 'options' => Teacher::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number'],
            ['name' => 'room', 'label' => 'Room', 'type' => 'text'],
        ];
    }

    protected function rules(?Model $record): array
    {
        return ['grade_level_id' => ['required', 'exists:grade_levels,id'], 'name' => ['required', 'string', 'max:20'],
                'academic_year_id' => ['required', 'exists:academic_years,id'], 'class_teacher_id' => ['nullable', 'exists:teachers,id'],
                'capacity' => ['nullable', 'integer', 'between:1,200'], 'room' => ['nullable', 'string', 'max:50']];
    }

    protected function beforeSave(array &$data, ?Model $record): void
    {
        $data['class_teacher_id'] = $data['class_teacher_id'] ?? null;
        $data['capacity'] = $data['capacity'] ?? 40;

        $dup = Section::where('grade_level_id', $data['grade_level_id'])->where('academic_year_id', $data['academic_year_id'])
            ->where('name', $data['name'])->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists();
        if ($dup) throw new LmsException('That class already exists for this academic year.');
    }

    protected function extraColumns(): array
    {
        return ['Year' => fn ($r) => $r->academicYear->name ?? '', 'Class teacher' => fn ($r) => $r->classTeacher->name ?? '—',
                'Room' => fn ($r) => $r->room, 'Students' => fn ($r) => $r->students_count . ' / ' . $r->capacity];
    }

    /* ------------------------------------------------------------ class page: students + subject teachers */

    public function show(Section $section)
    {
        $section->load(['gradeLevel', 'academicYear', 'classTeacher']);
        return view('admin.school.sections.show', [
            'section'     => $section,
            'students'    => Student::where('section_id', $section->id)->orderBy('name')->get(),
            'assignments' => SectionSubject::with(['subject', 'teacher'])->where('section_id', $section->id)->get()->sortBy('subject.name'),
            'subjects'    => Subject::orderBy('name')->get(['id', 'name']),
            'teachers'    => Teacher::where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function assign(Request $request, Section $section)
    {
        $data = $request->validate(['subject_id' => ['required', 'exists:subjects,id'], 'teacher_id' => ['nullable', 'exists:teachers,id']]);
        SectionSubject::updateOrCreate(['section_id' => $section->id, 'subject_id' => $data['subject_id']], ['teacher_id' => $data['teacher_id'] ?? null]);
        return back()->with('success', 'Subject assignment saved.');
    }

    public function unassign(Section $section, SectionSubject $assignment)
    {
        abort_unless($assignment->section_id === $section->id, 404);
        $assignment->delete();
        return back()->with('success', 'Subject removed from the class.');
    }
}
