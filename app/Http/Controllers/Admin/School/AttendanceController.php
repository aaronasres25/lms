<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Section;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Daily attendance. One controller, two entrances: back-office (perm attendance) and the teacher portal. */
class AttendanceController extends Controller
{
    private function teacherSide(Request $r): bool { return $r->user()->isTeacher(); }
    private function prefix(Request $r): string { return $this->teacherSide($r) ? 'teacher.attendance' : 'admin.school.attendance'; }

    private function sections(Request $r)
    {
        $q = Section::with(['gradeLevel', 'academicYear'])->orderBy('grade_level_id')->orderBy('name');
        if ($this->teacherSide($r)) $q->whereIn('id', $r->user()->teacher?->managedSectionIds() ?? []);
        return $q->get();
    }

    private function parseDate(?string $value, Carbon $fallback): Carbon
    {
        try { return $value ? Carbon::parse($value)->startOfDay() : $fallback; } catch (\Throwable $e) { return $fallback; }
    }

    public function index(Request $request)
    {
        $sections = $this->sections($request);
        $section = $request->filled('section_id') ? $sections->firstWhere('id', (int) $request->query('section_id')) : null;
        $mode = $request->query('mode') === 'report' ? 'report' : 'mark';
        $date = min($this->parseDate($request->query('date'), today()), today());
        $month = $this->parseDate($request->query('month') ? $request->query('month') . '-01' : null, today()->startOfMonth())->startOfMonth();

        $students = collect(); $existing = collect(); $report = [];
        if ($section) {
            $students = Student::where('section_id', $section->id)->where('status', 'active')->orderBy('name')->get();

            if ($mode === 'mark') {
                $existing = Attendance::where('section_id', $section->id)->whereDate('date', $date->toDateString())->get()->keyBy('student_id');
            } else {
                $rows = Attendance::where('section_id', $section->id)->whereDate('date', '>=', $month->toDateString())
                    ->whereDate('date', '<=', $month->copy()->endOfMonth()->toDateString())
                    ->selectRaw('student_id, status, count(*) as c')->groupBy('student_id', 'status')->get();
                foreach ($students as $s) {
                    $r = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
                    foreach ($rows->where('student_id', $s->id) as $row) $r[$row->status] = (int) $row->c;
                    $r['total'] = array_sum($r);
                    $r['percent'] = $r['total'] ? round(($r['present'] + $r['late']) / $r['total'] * 100, 1) : null;
                    $report[$s->id] = $r;
                }
            }
        }

        return view('school.attendance', [
            'layout' => $this->teacherSide($request) ? 'layouts.portal' : 'layouts.admin', 'prefix' => $this->prefix($request),
            'canSave' => $this->teacherSide($request) || $request->user()->canDo('attendance', 'update'),
            'sections' => $sections, 'section' => $section, 'students' => $students, 'existing' => $existing,
            'date' => $date, 'month' => $month, 'mode' => $mode, 'report' => $report, 'statuses' => config('school.attendance_statuses'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'section_id' => ['required', 'exists:sections,id'], 'date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', 'array'], 'status.*' => ['in:present,absent,late,excused'],
            'remark' => ['nullable', 'array'], 'remark.*' => ['nullable', 'string', 'max:200'],
        ]);
        $section = Section::findOrFail($data['section_id']);

        if ($this->teacherSide($request)) abort_unless(in_array($section->id, $request->user()->teacher?->managedSectionIds() ?? [], true), 403);
        else abort_unless($request->user()->canDo('attendance', 'update'), 403);

        $valid = Student::where('section_id', $section->id)->pluck('id')->all();
        $date = Carbon::parse($data['date'])->toDateString();

        DB::transaction(function () use ($data, $section, $valid, $date, $request) {
            foreach ($data['status'] as $studentId => $status) {
                if (! in_array((int) $studentId, $valid, true)) continue;
                $payload = ['section_id' => $section->id, 'status' => $status, 'remark' => $data['remark'][$studentId] ?? null, 'marked_by' => $request->user()->id];

                // whereDate keeps this portable (SQLite stores dates as text)
                $row = Attendance::where('student_id', $studentId)->whereDate('date', $date)->first();
                $row ? $row->update($payload) : Attendance::create($payload + ['student_id' => $studentId, 'date' => $date]);
            }
        });

        return redirect()->route($this->prefix($request) . '.index', ['section_id' => $section->id, 'date' => $date])->with('success', 'Attendance saved.');
    }
}
