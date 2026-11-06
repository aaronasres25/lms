<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Admin\School\TimetableController;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\Student;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;

class TeacherPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $teacher = $request->user()->teacher;
        $mine = $teacher ? TimetableSlot::with(['section.gradeLevel', 'subject'])->where('teacher_id', $teacher->id) : null;
        $sectionIds = $teacher ? $teacher->managedSectionIds() : [];

        return view('portal.teacher-dashboard', [
            'teacher'       => $teacher,
            'classSections' => $teacher ? Section::with('gradeLevel')->withCount('students')->where('class_teacher_id', $teacher->id)->get() : collect(),
            'assignments'   => $teacher ? SectionSubject::with(['section.gradeLevel', 'subject'])->where('teacher_id', $teacher->id)->get() : collect(),
            'today'         => $mine ? (clone $mine)->where('day_of_week', today()->dayOfWeekIso)->orderBy('period_no')->get() : collect(),
            'studentCount'  => Student::whereIn('section_id', $sectionIds)->where('status', 'active')->count(),
            'notices'       => Announcement::for('teachers')->latest('id')->limit(5)->get(),
        ]);
    }

    public function timetable(Request $request)
    {
        $teacher = $request->user()->teacher;
        $slots = $teacher ? TimetableSlot::with(['section.gradeLevel', 'subject'])->where('teacher_id', $teacher->id)->get() : collect();
        return view('portal.teacher-timetable', ['grid' => TimetableController::grid($slots), 'teacher' => $teacher]);
    }
}
