<?php

namespace Tests\Feature;

use App\Models\AccessRight;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Book;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\FeeInvoice;
use App\Models\FeeType;
use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $role): User
    {
        return User::where('email', "{$role}@lms.test")->firstOrFail();
    }

    public function test_each_role_lands_on_its_own_home(): void
    {
        $this->post('/login', ['email' => 'student@lms.test', 'password' => 'password'])->assertRedirect(route('student.dashboard'));
        $this->post('/logout');
        $this->post('/login', ['email' => 'teacher@lms.test', 'password' => 'password'])->assertRedirect(route('teacher.dashboard'));
    }

    public function test_portals_are_isolated(): void
    {
        $student = $this->user('student');
        $teacher = $this->user('teacher');

        $this->actingAs($student)->get('/teacher')->assertForbidden();
        $this->actingAs($student)->get('/admin')->assertForbidden();
        $this->actingAs($teacher)->get('/student')->assertForbidden();
        $this->actingAs($teacher)->get('/admin')->assertForbidden();

        foreach (['/student', '/student/timetable', '/student/attendance', '/student/results', '/student/fees', '/student/library', '/student/notifications'] as $url) {
            $this->actingAs($student)->get($url)->assertOk();
        }
        foreach (['/teacher', '/teacher/timetable', '/teacher/attendance', '/teacher/marks', '/teacher/notifications'] as $url) {
            $this->actingAs($teacher)->get($url)->assertOk();
        }
    }

    public function test_admin_school_screens_load(): void
    {
        $admin = $this->user('admin');
        $section = Section::firstOrFail();
        $student = Student::firstOrFail();

        foreach (['/admin/school/students', '/admin/school/students/create', "/admin/school/students/{$student->id}", "/admin/school/students/{$student->id}/edit",
                  '/admin/school/teachers', '/admin/school/teachers/create', '/admin/school/sections', "/admin/school/sections/{$section->id}",
                  '/admin/school/attendance', "/admin/school/attendance?section_id={$section->id}", "/admin/school/attendance?section_id={$section->id}&mode=report",
                  '/admin/school/timetable', "/admin/school/timetable?section_id={$section->id}&edit=1", '/admin/school/exams', '/admin/school/marks',
                  '/admin/school/invoices', '/admin/school/invoices/create', '/admin/school/promotion', "/admin/school/promotion?from={$section->id}",
                  '/admin/school/academic-years', '/admin/school/terms', '/admin/school/grade-levels', '/admin/school/subjects',
                  '/admin/school/grade-scale', '/admin/school/fee-types', '/admin/school/announcements'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_school_data_is_hidden_until_rights_are_granted(): void
    {
        $clerk = $this->user('user');
        $this->actingAs($clerk)->get('/admin/school/students')->assertForbidden();

        AccessRight::create(['user_id' => $clerk->id, 'resource' => 'students', 'can_read' => true, 'can_create' => false, 'can_update' => false, 'can_delete' => false]);
        $this->actingAs($clerk->fresh())->get('/admin/school/students')->assertOk();
        $this->actingAs($clerk->fresh())->get('/admin/school/students/create')->assertForbidden();
    }

    public function test_admin_can_enrol_a_student_with_a_login_and_library_membership(): void
    {
        $section = Section::firstOrFail();
        $this->actingAs($this->user('admin'))->post('/admin/school/students', [
            'name' => 'New Pupil', 'status' => 'active', 'section_id' => $section->id,
            'create_login' => 1, 'login_email' => 'pupil@lms.test',
        ])->assertRedirect();

        $user = User::where('email', 'pupil@lms.test')->firstOrFail();
        $this->assertSame('student', $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertNotNull($user->member);                                  // can borrow library books
        $this->assertSame($user->id, Student::where('name', 'New Pupil')->value('user_id'));
    }

    public function test_student_requests_a_book_and_cannot_request_twice(): void
    {
        $student = $this->user('student');
        $book = Book::where('state', 'released')->firstOrFail();

        $this->actingAs($student)->post(route('student.library.request', $book))->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('loans', ['book_id' => $book->id, 'state' => 'draft', 'member_id' => $student->member->id]);

        $this->actingAs($student)->post(route('student.library.request', $book))->assertSessionHas('error');
        $this->assertSame(1, $student->member->loans()->count());

        // library staff are told
        $this->assertTrue($this->user('admin')->notifications()->where('data', 'like', '%requested%')->exists());
    }

    public function test_class_teacher_takes_attendance_and_resaving_updates_it(): void
    {
        $teacher = $this->user('teacher');
        $section = Section::firstOrFail();
        $student = Student::firstOrFail();
        $payload = fn (string $status) => ['section_id' => $section->id, 'date' => today()->toDateString(), 'status' => [$student->id => $status]];

        $this->actingAs($teacher)->post('/teacher/attendance', $payload('absent'))->assertRedirect();
        $this->actingAs($teacher)->post('/teacher/attendance', $payload('late'))->assertRedirect();

        $this->assertSame(1, Attendance::where('student_id', $student->id)->count());
        $this->assertSame('late', Attendance::where('student_id', $student->id)->value('status'));
    }

    public function test_marks_report_card_and_publishing(): void
    {
        $teacher = $this->user('teacher');
        $student = Student::firstOrFail();
        $section = Section::firstOrFail();
        $assignment = SectionSubject::where('teacher_id', Teacher::where('user_id', $teacher->id)->value('id'))->firstOrFail();
        $exam = Exam::create(['academic_year_id' => AcademicYear::current()->id, 'name' => 'Mid-term', 'max_marks' => 100, 'is_published' => false]);

        $row = ['exam_id' => $exam->id, 'section_id' => $section->id, 'subject_id' => $assignment->subject_id];

        // too many marks is refused
        $this->actingAs($teacher)->post('/teacher/marks', $row + ['marks' => [$student->id => 150]])->assertSessionHas('error');
        $this->assertSame(0, ExamMark::count());

        $this->actingAs($teacher)->post('/teacher/marks', $row + ['marks' => [$student->id => 85]])->assertSessionHas('success');
        $this->assertEquals(85.0, ExamMark::first()->marks);

        // the student cannot see it until it is published
        $this->actingAs($this->user('student'))->get(route('student.report-card', $exam))->assertForbidden();
        $exam->update(['is_published' => true]);
        $this->actingAs($this->user('student'))->get(route('student.report-card', $exam))->assertOk()->assertSee('Mid-term');
    }

    public function test_fee_invoice_payments_update_the_balance(): void
    {
        $admin = $this->user('admin');
        $section = Section::firstOrFail();
        $fee = FeeType::where('name', 'Tuition')->firstOrFail();

        $this->actingAs($admin)->post('/admin/school/invoices', [
            'section_id' => $section->id, 'fee_types' => [$fee->id], 'amounts' => [$fee->id => 1000],
        ])->assertRedirect();

        $invoice = FeeInvoice::firstOrFail();
        $this->assertSame('unpaid', $invoice->status);
        $this->assertEquals(1000.0, $invoice->total);

        $pay = fn ($amount) => $this->actingAs($admin)->post("/admin/school/invoices/{$invoice->id}/payments", ['amount' => $amount, 'paid_on' => today()->toDateString(), 'method' => 'cash']);

        $pay(400);
        $this->assertSame('partial', $invoice->fresh()->status);
        $pay(5000)->assertSessionHas('error');                    // more than the balance
        $pay(600);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertEquals(0.0, $invoice->fresh()->balance);
    }

    public function test_a_teacher_cannot_be_double_booked(): void
    {
        $admin = $this->user('admin');
        $a = Section::firstOrFail();
        $b = Section::create(['grade_level_id' => $a->grade_level_id, 'academic_year_id' => $a->academic_year_id, 'name' => 'B', 'capacity' => 30]);
        $math = SectionSubject::where('section_id', $a->id)->firstOrFail();
        SectionSubject::create(['section_id' => $b->id, 'subject_id' => $math->subject_id, 'teacher_id' => $math->teacher_id]);

        $slot = fn (Section $s) => ['section_id' => $s->id, 'slots' => [1 => [1 => $math->subject_id]]];
        $this->actingAs($admin)->post('/admin/school/timetable', $slot($a))->assertSessionHas('success');
        $this->actingAs($admin)->post('/admin/school/timetable', $slot($b))->assertSessionHas('error');
    }
}
