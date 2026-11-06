<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\GradeScale;
use App\Models\Student;
use Illuminate\Http\Request;

/** Printable report card for one student and one exam (back-office, or the student's own portal once published). */
class ReportCardController extends Controller
{
    public function show(Request $request, Student $student, Exam $exam)
    {
        $user = $request->user();
        if ($user->isStudent()) {
            abort_unless($user->student && $user->student->id === $student->id && $exam->is_published, 403);
        } else {
            abort_unless($user->canDo('exams', 'read'), 403);
        }

        $marks = ExamMark::with('subject')->where('exam_id', $exam->id)->where('student_id', $student->id)->get()->sortBy('subject.name');

        $rows = $marks->map(function ($m) use ($exam) {
            $pct = $m->marks === null ? null : round($m->marks / $exam->max_marks * 100, 1);
            [$grade, $remark] = GradeScale::gradeFor($pct);
            return ['subject' => $m->subject->name, 'marks' => $m->marks, 'max' => $exam->max_marks, 'pct' => $pct, 'grade' => $grade, 'remark' => $remark, 'note' => $m->remark];
        })->values();

        $scored = $marks->whereNotNull('marks');
        $total = $scored->sum('marks');
        $maxTotal = $scored->count() * $exam->max_marks;
        $average = $maxTotal ? round($total / $maxTotal * 100, 1) : null;

        // position in the class for this exam
        $position = null; $classSize = null;
        $sectionId = $marks->first()->section_id ?? $student->section_id;
        if ($sectionId) {
            $totals = ExamMark::where('exam_id', $exam->id)->where('section_id', $sectionId)->selectRaw('student_id, sum(marks) as t')
                ->groupBy('student_id')->orderByDesc('t')->pluck('t', 'student_id')->keys()->values();
            $classSize = $totals->count();
            $idx = $totals->search($student->id);
            $position = $idx === false ? null : $idx + 1;
        }

        $student->load(['section.gradeLevel', 'section.classTeacher']);

        return view('school.report-card', [
            'student' => $student, 'exam' => $exam->load(['year', 'term']), 'rows' => $rows, 'total' => $total, 'maxTotal' => $maxTotal,
            'average' => $average, 'overall' => GradeScale::gradeFor($average)[0], 'position' => $position, 'classSize' => $classSize,
            'attendance' => $student->attendanceSummary($exam->year?->start_date, $exam->year?->end_date),
        ]);
    }
}
