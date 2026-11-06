<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Exam marks entry. Teachers can enter marks only for the subjects they teach in a class. */
class MarksController extends Controller
{
    private function teacherSide(Request $r): bool { return $r->user()->isTeacher(); }
    private function prefix(Request $r): string { return $this->teacherSide($r) ? 'teacher.marks' : 'admin.school.marks'; }

    public function index(Request $request)
    {
        $teacher = $this->teacherSide($request) ? $request->user()->teacher : null;

        $secQ = Section::with(['gradeLevel', 'academicYear'])->orderBy('grade_level_id')->orderBy('name');
        if ($this->teacherSide($request)) $secQ->whereIn('id', SectionSubject::where('teacher_id', $teacher?->id)->pluck('section_id'));
        $sections = $secQ->get();

        $exams = Exam::orderByDesc('id')->get();
        $section = $request->filled('section_id') ? $sections->firstWhere('id', (int) $request->query('section_id')) : null;
        $exam = $request->filled('exam_id') ? $exams->firstWhere('id', (int) $request->query('exam_id')) : null;

        $assignments = collect();
        if ($section) {
            $aq = SectionSubject::with('subject')->where('section_id', $section->id);
            if ($teacher) $aq->where('teacher_id', $teacher->id);
            $assignments = $aq->get()->sortBy('subject.name');
        }
        $assignment = $request->filled('subject_id') ? $assignments->firstWhere('subject_id', (int) $request->query('subject_id')) : null;

        $students = collect(); $marks = collect();
        if ($section && $exam && $assignment) {
            $students = Student::where('section_id', $section->id)->where('status', 'active')->orderBy('name')->get();
            $marks = ExamMark::where('exam_id', $exam->id)->where('subject_id', $assignment->subject_id)->get()->keyBy('student_id');
        }

        return view('school.marks', [
            'layout' => $this->teacherSide($request) ? 'layouts.portal' : 'layouts.admin', 'prefix' => $this->prefix($request),
            'canSave' => $this->teacherSide($request) || $request->user()->canDo('exams', 'update'),
            'exams' => $exams, 'exam' => $exam, 'sections' => $sections, 'section' => $section,
            'assignments' => $assignments, 'assignment' => $assignment, 'students' => $students, 'marks' => $marks,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'exam_id' => ['required', 'exists:exams,id'], 'section_id' => ['required', 'exists:sections,id'], 'subject_id' => ['required', 'exists:subjects,id'],
            'marks' => ['nullable', 'array'], 'marks.*' => ['nullable', 'numeric', 'min:0'], 'remark' => ['nullable', 'array'], 'remark.*' => ['nullable', 'string', 'max:200'],
        ]);
        $exam = Exam::findOrFail($data['exam_id']);

        $assignment = SectionSubject::where('section_id', $data['section_id'])->where('subject_id', $data['subject_id'])->firstOrFail();
        if ($this->teacherSide($request)) abort_unless($assignment->teacher_id && $assignment->teacher_id === $request->user()->teacher?->id, 403);
        else abort_unless($request->user()->canDo('exams', 'update'), 403);

        foreach (($data['marks'] ?? []) as $sid => $value) {
            if ($value !== null && $value !== '' && (float) $value > $exam->max_marks) {
                return back()->withInput()->with('error', "Marks cannot be more than {$exam->max_marks} for this exam.");
            }
        }

        $valid = Student::where('section_id', $data['section_id'])->pluck('id')->all();
        DB::transaction(function () use ($data, $valid, $request) {
            foreach (($data['marks'] ?? []) as $sid => $value) {
                if (! in_array((int) $sid, $valid, true)) continue;
                ExamMark::updateOrCreate(
                    ['exam_id' => $data['exam_id'], 'student_id' => $sid, 'subject_id' => $data['subject_id']],
                    ['section_id' => $data['section_id'], 'marks' => ($value === '' || $value === null) ? null : $value,
                     'remark' => $data['remark'][$sid] ?? null, 'entered_by' => $request->user()->id]);
            }
        });

        return redirect()->route($this->prefix($request) . '.index', ['exam_id' => $data['exam_id'], 'section_id' => $data['section_id'], 'subject_id' => $data['subject_id']])
            ->with('success', 'Marks saved.');
    }
}
