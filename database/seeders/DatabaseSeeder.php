<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Department;
use App\Models\FeeType;
use App\Models\GradeLevel;
use App\Models\GradeScale;
use App\Models\Enrollment;
use App\Models\Member;
use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedReferenceData();

        if (app()->environment('production')) {
            $this->seedProductionAdmin();     // no demo accounts / demo data in production
            return;
        }
        $this->seedDemoData();
    }

    /** Departments + categories - safe to run repeatedly. */
    private function seedReferenceData(): void
    {
        $tree = [
            'Production' => [], 'Fabrication' => [],
            'ICT' => ['System Development', 'Infrastructure', 'Customer Support', 'CCTV Operator'],
            'Finance' => ['General Finance' => ['Registry'], 'Closing Finance'],
            'Commerce' => [], 'Coffee' => [], 'Purchase' => [], 'Documentation' => [], 'Digital Marketing' => [],
            'HR' => [], 'Management' => [], 'Scrap' => [], 'Security' => [], 'Drivers' => [],
        ];
        $this->seedDepartments($tree, null);

        foreach (['Policy', 'Procedure', 'Manual', 'Technical Guide', 'Report', 'Training Material'] as $c) {
            Category::firstOrCreate(['name' => $c]);
        }

        $this->seedSchoolReference();
    }

    /** School set-up that every installation needs: current academic year, terms, grade levels, grade scale. */
    private function seedSchoolReference(): void
    {
        $y = now()->month >= 9 ? now()->year : now()->year - 1;
        $year = AcademicYear::firstOrCreate(['name' => $y . '/' . ($y + 1)],
            ['start_date' => Carbon::create($y, 9, 1), 'end_date' => Carbon::create($y + 1, 7, 31), 'is_current' => ! AcademicYear::where('is_current', true)->exists()]);

        foreach ([['Term 1', [$y, 9, 1], [$y, 12, 20]], ['Term 2', [$y + 1, 1, 6], [$y + 1, 3, 31]], ['Term 3', [$y + 1, 4, 8], [$y + 1, 7, 31]]] as [$name, $from, $to]) {
            Term::firstOrCreate(['academic_year_id' => $year->id, 'name' => $name], ['start_date' => Carbon::create(...$from), 'end_date' => Carbon::create(...$to)]);
        }

        GradeLevel::firstOrCreate(['name' => 'KG'], ['level_order' => 0]);
        for ($i = 1; $i <= 12; $i++) GradeLevel::firstOrCreate(['name' => "Grade {$i}"], ['level_order' => $i]);

        foreach ([['A', 90, 'Excellent'], ['B', 80, 'Very good'], ['C', 70, 'Good'], ['D', 60, 'Satisfactory'], ['E', 50, 'Pass'], ['F', 0, 'Fail']] as [$l, $min, $remark]) {
            GradeScale::firstOrCreate(['name' => $l], ['min_percent' => $min, 'remark' => $remark]);
        }
    }

    /** Production: one admin taken from ADMIN_EMAIL / ADMIN_PASSWORD (never overwrites an existing account). */
    private function seedProductionAdmin(): void
    {
        $email = config('lms.admin_email');
        $password = config('lms.admin_password');
        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set - no admin created.');
            return;
        }
        User::firstOrCreate(['email' => $email], [
            'name' => config('lms.admin_name'), 'password' => $password, 'role' => 'admin', 'active' => true,
        ]);
    }

    /** Local / testing only: one demo account per role (password "password"). */
    private function seedDemoData(): void
    {
        $users = [];
        foreach (['admin' => 'Administrator', 'manager' => 'Library Manager', 'qa' => 'Library QA', 'user' => 'Library Clerk', 'reader' => 'Demo Reader'] as $role => $name) {
            $users[$role] = User::firstOrCreate(['email' => "{$role}@lms.test"], ['name' => $name, 'password' => 'password', 'role' => $role]);
        }

        $author = Author::firstOrCreate(['name' => 'AMG Documentation Team']);
        Book::firstOrCreate(
            ['name' => 'Employee Handbook', 'publication_year' => 2025, 'version' => '1.0',
             'department_id' => Department::where('name', 'HR')->value('id'), 'category_id' => Category::where('name', 'Policy')->value('id')],
            ['author_id' => $author->id, 'copies_total' => 3, 'fine_per_day' => 2.50, 'state' => 'released',
             'approval_date' => today(), 'expiry_date' => today()->addYears(2), 'description' => 'Demo physical copy of the employee handbook.']
        );
        Member::firstOrCreate(['name' => 'Demo Reader', 'user_id' => $users['reader']->id], [
            'membership_start' => today(), 'membership_end' => today()->addYear(), 'max_books' => 5,
        ]);

        $this->seedSchoolDemo();
    }

    /** Local / testing only: a teacher, a student, a class and its subjects. */
    private function seedSchoolDemo(): void
    {
        $year = AcademicYear::current();
        $subjects = collect(['Mathematics' => 'MATH', 'English' => 'ENG', 'Science' => 'SCI', 'History' => 'HIS'])
            ->map(fn ($code, $name) => Subject::firstOrCreate(['name' => $name], ['code' => $code]));

        foreach (['Tuition' => 1000, 'Library fee' => 50, 'Transport' => 300] as $name => $amount) FeeType::firstOrCreate(['name' => $name], ['amount' => $amount]);

        $tUser = User::firstOrCreate(['email' => 'teacher@lms.test'], ['name' => 'Demo Teacher', 'password' => 'password', 'role' => 'teacher']);
        $teacher = Teacher::firstOrCreate(['employee_no' => 'EMP0001'], ['user_id' => $tUser->id, 'name' => 'Demo Teacher', 'email' => 'teacher@lms.test', 'qualification' => 'BEd', 'joined_on' => today(), 'status' => 'active']);
        Member::firstOrCreate(['name' => 'Demo Teacher', 'user_id' => $tUser->id], ['membership_start' => today(), 'membership_end' => today()->addYear(), 'max_books' => config('school.teacher_max_books')]);

        $section = Section::firstOrCreate(['grade_level_id' => GradeLevel::where('name', 'Grade 5')->value('id'), 'academic_year_id' => $year->id, 'name' => 'A'],
            ['class_teacher_id' => $teacher->id, 'capacity' => 40, 'room' => 'Room 5A']);
        foreach ($subjects as $subject) SectionSubject::firstOrCreate(['section_id' => $section->id, 'subject_id' => $subject->id], ['teacher_id' => $teacher->id]);

        $sUser = User::firstOrCreate(['email' => 'student@lms.test'], ['name' => 'Demo Student', 'password' => 'password', 'role' => 'student']);
        $student = Student::firstOrCreate(['admission_no' => 'ADM0001'], ['user_id' => $sUser->id, 'name' => 'Demo Student', 'gender' => 'female',
            'guardian_name' => 'Demo Guardian', 'guardian_phone' => '+251900000000', 'section_id' => $section->id, 'status' => 'active', 'admitted_on' => today()]);
        Enrollment::firstOrCreate(['student_id' => $student->id, 'academic_year_id' => $year->id], ['section_id' => $section->id]);
        Member::firstOrCreate(['name' => 'Demo Student', 'user_id' => $sUser->id], ['membership_start' => today(), 'membership_end' => $year->end_date->gte(today()) ? $year->end_date : today()->addYear(), 'max_books' => config('school.student_max_books')]);
    }

    private function seedDepartments(array $nodes, ?int $parentId): void
    {
        foreach ($nodes as $key => $value) {
            $name = is_int($key) ? $value : $key;
            $dept = Department::firstOrCreate(['name' => $name, 'parent_id' => $parentId]);
            if (! is_int($key) && is_array($value)) {
                $this->seedDepartments($value, $dept->id);
            }
        }
    }
}
