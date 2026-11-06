<?php

namespace App\Http\Controllers\Admin\School;

use App\Exceptions\LmsException;
use App\Http\Controllers\Controller;
use App\Models\SectionSubject;
use App\Models\Sequence;
use App\Models\Teacher;
use App\Support\PersonAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $q = Teacher::withCount(['classSections', 'assignments']);
        if ($s = trim((string) $request->query('q', ''))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('employee_no', 'like', "%{$s}%"));
        }
        return view('admin.school.teachers.index', ['teachers' => $q->orderBy('name')->paginate($this->perPage($request))->withQueryString()]);
    }

    public function create()
    {
        return view('admin.school.teachers.form', ['teacher' => new Teacher(['status' => 'active', 'joined_on' => today()])]);
    }

    private function rules(?Teacher $teacher = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'employee_no' => ['nullable', 'string', 'max:50', 'unique:teachers,employee_no' . ($teacher ? ',' . $teacher->id : '')],
            'phone' => ['nullable', 'string', 'max:50'], 'email' => ['nullable', 'email', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'], 'joined_on' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'create_login' => ['nullable', 'boolean'],
            'login_email' => ['nullable', 'required_if:create_login,1', 'email', 'max:255', 'unique:users,email'],
            'login_password' => ['nullable', 'string', 'min:8', 'max:100'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $plain = null;
        $teacher = null;

        DB::transaction(function () use ($request, $data, &$plain, &$teacher) {
            $payload = Arr::except($data, ['create_login', 'login_email', 'login_password']);
            $payload['employee_no'] = ($payload['employee_no'] ?? null) ?: 'EMP' . str_pad((string) Sequence::next('teacher'), 4, '0', STR_PAD_LEFT);

            if ($request->boolean('create_login')) {
                [$user, $plain] = PersonAccounts::create('teacher', $payload['name'], $data['login_email'], $data['login_password'] ?? null, config('school.teacher_max_books'));
                $payload['user_id'] = $user->id;
                $payload['email'] = $payload['email'] ?? $data['login_email'];
            }
            $teacher = Teacher::create($payload);
        });

        return redirect()->route('admin.school.teachers.edit', $teacher)->with('success', "Teacher {$teacher->name} added ({$teacher->employee_no}).")->with('temp_password', $plain);
    }

    public function edit(Teacher $teacher)
    {
        $teacher->load(['user', 'classSections.gradeLevel']);
        return view('admin.school.teachers.form', [
            'teacher' => $teacher,
            'assignments' => SectionSubject::with(['section.gradeLevel', 'subject'])->where('teacher_id', $teacher->id)->get(),
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $request->validate($this->rules($teacher));
        $plain = null;

        DB::transaction(function () use ($request, $data, $teacher, &$plain) {
            $payload = Arr::except($data, ['create_login', 'login_email', 'login_password']);
            $payload['employee_no'] = ($payload['employee_no'] ?? null) ?: $teacher->employee_no;

            if (! $teacher->user_id && $request->boolean('create_login')) {
                [$user, $plain] = PersonAccounts::create('teacher', $payload['name'], $data['login_email'], $data['login_password'] ?? null, config('school.teacher_max_books'));
                $payload['user_id'] = $user->id;
            }
            $teacher->update($payload);
            $teacher->user?->update(['name' => $teacher->name, 'active' => $teacher->status === 'active']);
        });

        return redirect()->route('admin.school.teachers.edit', $teacher)->with('success', 'Teacher updated.')->with('temp_password', $plain);
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();   // sections / assignments keep working (teacher is simply unassigned)
        return redirect()->route('admin.school.teachers.index')->with('success', 'Teacher removed.');
    }
}
