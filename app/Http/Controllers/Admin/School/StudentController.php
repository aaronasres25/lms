<?php

namespace App\Http\Controllers\Admin\School;

use App\Exceptions\LmsException;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\FeeInvoice;
use App\Models\Loan;
use App\Models\Section;
use App\Models\Sequence;
use App\Models\Student;
use App\Support\PersonAccounts;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public static function sectionOptions(): array
    {
        return Section::with(['gradeLevel', 'academicYear'])->orderBy('grade_level_id')->orderBy('name')->get()
            ->mapWithKeys(fn ($s) => [$s->id => $s->label . ' · ' . ($s->academicYear->name ?? '')])->all();
    }

    public function index(Request $request)
    {
        $q = Student::with('section.gradeLevel');
        if ($s = trim((string) $request->query('q', ''))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('admission_no', 'like', "%{$s}%"));
        }
        if ($request->filled('section_id')) $q->where('section_id', $request->query('section_id'));
        if ($request->filled('status')) $q->where('status', $request->query('status'));

        return view('admin.school.students.index', [
            'students' => $q->orderBy('name')->paginate($this->perPage($request))->withQueryString(),
            'sections' => self::sectionOptions(),
        ]);
    }

    public function create()
    {
        return view('admin.school.students.form', ['student' => new Student(['status' => 'active', 'admitted_on' => today()]), 'sections' => self::sectionOptions()]);
    }

    private function rules(?Student $student = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'admission_no' => ['nullable', 'string', 'max:50', 'unique:students,admission_no' . ($student ? ',' . $student->id : '')],
            'gender' => ['nullable', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:50'], 'address' => ['nullable', 'string', 'max:500'],
            'guardian_name' => ['nullable', 'string', 'max:255'], 'guardian_phone' => ['nullable', 'string', 'max:50'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'status' => ['required', 'in:' . implode(',', array_keys(config('school.student_statuses')))],
            'admitted_on' => ['nullable', 'date'],
            'create_login' => ['nullable', 'boolean'],
            'login_email' => ['nullable', 'required_if:create_login,1', 'email', 'max:255', 'unique:users,email'],
            'login_password' => ['nullable', 'string', 'min:8', 'max:100'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $plain = null;
        $student = null;

        DB::transaction(function () use ($request, $data, &$plain, &$student) {
            $payload = Arr::except($data, ['create_login', 'login_email', 'login_password']);
            $payload['admission_no'] = ($payload['admission_no'] ?? null) ?: 'ADM' . date('y') . str_pad((string) Sequence::next('student'), 4, '0', STR_PAD_LEFT);
            $payload['admitted_on'] = $payload['admitted_on'] ?? today();

            if ($request->boolean('create_login')) {
                [$user, $plain] = PersonAccounts::create('student', $payload['name'], $data['login_email'], $data['login_password'] ?? null, config('school.student_max_books'));
                $payload['user_id'] = $user->id;
            }
            $student = Student::create($payload);
            $this->enroll($student);
        });

        return redirect()->route('admin.school.students.show', $student)->with('success', "Student {$student->name} added ({$student->admission_no}).")
            ->with('temp_password', $plain);
    }

    private function enroll(Student $student): void
    {
        if (! $student->section_id) return;
        $section = Section::find($student->section_id);
        Enrollment::updateOrCreate(['student_id' => $student->id, 'academic_year_id' => $section->academic_year_id], ['section_id' => $section->id]);
    }

    public function show(Student $student)
    {
        $student->load(['section.gradeLevel', 'section.academicYear', 'section.classTeacher', 'user']);
        $member = $student->libraryMember();

        return view('admin.school.students.show', [
            'student'    => $student,
            'member'     => $member,
            'loans'      => $member ? Loan::with('book')->where('member_id', $member->id)->latest('id')->limit(8)->get() : collect(),
            'attendance' => $student->attendanceSummary(now()->subDays(30), now()),
            'exams'      => Exam::whereHas('marks', fn ($m) => $m->where('student_id', $student->id))->orderByDesc('id')->get(),
            'invoices'   => FeeInvoice::withSum('payments', 'amount')->where('student_id', $student->id)->latest('id')->get(),
            'history'    => Enrollment::with('section.gradeLevel')->where('student_id', $student->id)->latest('id')->get(),
        ]);
    }

    public function edit(Student $student)
    {
        return view('admin.school.students.form', ['student' => $student, 'sections' => self::sectionOptions()]);
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate($this->rules($student));
        $plain = null;

        DB::transaction(function () use ($request, $data, $student, &$plain) {
            $payload = Arr::except($data, ['create_login', 'login_email', 'login_password']);
            $payload['admission_no'] = ($payload['admission_no'] ?? null) ?: $student->admission_no;

            // add a login later, if the student does not have one yet
            if (! $student->user_id && $request->boolean('create_login')) {
                [$user, $plain] = PersonAccounts::create('student', $payload['name'], $data['login_email'], $data['login_password'] ?? null, config('school.student_max_books'));
                $payload['user_id'] = $user->id;
            }
            $student->update($payload);
            $student->user?->update(['name' => $student->name]);
            $this->enroll($student);
        });

        return redirect()->route('admin.school.students.show', $student)->with('success', 'Student updated.')->with('temp_password', $plain);
    }

    public function destroy(Student $student)
    {
        try {
            $student->delete();
        } catch (QueryException $e) {
            throw new LmsException('This student has fee invoices and cannot be deleted. Mark the student Inactive instead.');
        }
        return redirect()->route('admin.school.students.index')->with('success', 'Student deleted.');
    }
}
