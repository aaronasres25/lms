<?php

namespace App\Http\Controllers\Admin\School;

use App\Exceptions\LmsException;
use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    /** [period][day] => slot */
    public static function grid($slots): array
    {
        $grid = [];
        foreach ($slots as $s) $grid[$s->period_no][$s->day_of_week] = $s;
        return $grid;
    }

    public function index(Request $request)
    {
        $sections = Section::with(['gradeLevel', 'academicYear'])->orderBy('grade_level_id')->orderBy('name')->get();
        $section = $request->filled('section_id') ? $sections->firstWhere('id', (int) $request->query('section_id')) : null;
        $canEdit = $request->user()->canDo('timetable', 'update');

        $slots = $section ? TimetableSlot::with(['subject', 'teacher'])->where('section_id', $section->id)->get() : collect();

        return view('admin.school.timetable', [
            'sections' => $sections, 'section' => $section, 'grid' => self::grid($slots),
            'edit' => $canEdit && $request->boolean('edit'), 'canEdit' => $canEdit,
            'assignments' => $section ? SectionSubject::with(['subject', 'teacher'])->where('section_id', $section->id)->get()->sortBy('subject.name') : collect(),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate(['section_id' => ['required', 'exists:sections,id'], 'slots' => ['nullable', 'array'], 'rooms' => ['nullable', 'array']]);
        $section = Section::findOrFail($data['section_id']);
        $assign = SectionSubject::with('teacher')->where('section_id', $section->id)->get()->keyBy('subject_id');

        DB::transaction(function () use ($data, $section, $assign) {
            TimetableSlot::where('section_id', $section->id)->delete();

            foreach (($data['slots'] ?? []) as $day => $periods) {
                foreach ($periods as $period => $subjectId) {
                    if (! $subjectId) continue;
                    if (! isset($assign[$subjectId])) throw new LmsException('Only subjects assigned to this class can be scheduled. Assign it on the class page first.');

                    $teacherId = $assign[$subjectId]->teacher_id;
                    if ($teacherId) {
                        $clash = TimetableSlot::with('section.gradeLevel')->where('teacher_id', $teacherId)->where('day_of_week', $day)
                            ->where('period_no', $period)->where('section_id', '!=', $section->id)->first();
                        if ($clash) {
                            $dayName = config('school.days')[$day] ?? $day;
                            throw new LmsException("{$assign[$subjectId]->teacher->name} is already teaching {$clash->section->label} on {$dayName}, period {$period}.");
                        }
                    }
                    TimetableSlot::create(['section_id' => $section->id, 'day_of_week' => $day, 'period_no' => $period, 'subject_id' => $subjectId,
                        'teacher_id' => $teacherId, 'room' => $data['rooms'][$day][$period] ?? null]);
                }
            }
        });

        return redirect()->route('admin.school.timetable.index', ['section_id' => $section->id])->with('success', 'Timetable saved.');
    }
}
