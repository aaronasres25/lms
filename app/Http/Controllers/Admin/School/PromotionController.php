<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** End-of-year: move a class up to the next class, or graduate it. */
class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->filled('from') ? Section::with(['gradeLevel', 'academicYear'])->find($request->query('from')) : null;

        return view('admin.school.promotion', [
            'sections' => StudentController::sectionOptions(),
            'from'     => $from,
            'students' => $from ? Student::where('section_id', $from->id)->where('status', 'active')->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', 'exists:sections,id'],
            'action' => ['required', 'in:promote,graduate'],
            'to' => ['required_if:action,promote', 'nullable', 'exists:sections,id'],
            'students' => ['required', 'array', 'min:1'],
            'students.*' => ['integer', 'exists:students,id'],
        ], ['students.required' => 'Tick at least one student.']);

        $from = Section::findOrFail($data['from']);
        $to = $data['action'] === 'promote' ? Section::findOrFail($data['to']) : null;
        $count = 0;

        DB::transaction(function () use ($data, $from, $to, &$count) {
            foreach (Student::whereIn('id', $data['students'])->where('section_id', $from->id)->get() as $student) {
                Enrollment::updateOrCreate(['student_id' => $student->id, 'academic_year_id' => $from->academic_year_id],
                    ['section_id' => $from->id, 'result' => $to ? 'promoted' : 'graduated']);

                if ($to) {
                    Enrollment::updateOrCreate(['student_id' => $student->id, 'academic_year_id' => $to->academic_year_id], ['section_id' => $to->id]);
                    $student->update(['section_id' => $to->id]);
                } else {
                    $student->update(['status' => 'graduated', 'section_id' => null]);
                }
                $count++;
            }
        });

        return redirect()->route('admin.school.promotion.index')->with('success', $to
            ? "{$count} student(s) promoted to {$to->label}." : "{$count} student(s) marked as graduated.");
    }
}
