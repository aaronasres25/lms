<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Book;
use App\Models\ExamMark;
use App\Models\FeeInvoice;
use App\Models\Loan;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');      // demo e-book PDFs are written here, not into the real storage folder
    }

    private function counts(): array
    {
        return [Book::count(), Student::count(), Teacher::count(), Loan::count(), TimetableSlot::count(), ExamMark::count(), Attendance::count(), FeeInvoice::count(), User::count()];
    }

    public function test_it_loads_library_and_school_data_and_is_repeatable(): void
    {
        $this->artisan('lms:demo-data', ['--force' => true])->assertExitCode(0);

        [$books, $students, $teachers, $loans, $slots, $marks, $attendance, $invoices] = $this->counts();
        $this->assertGreaterThan(15, $books);
        $this->assertGreaterThan(50, $students);
        $this->assertGreaterThanOrEqual(5, $teachers);
        $this->assertGreaterThan(6, $loans);
        $this->assertGreaterThan(50, $slots);
        $this->assertGreaterThan(500, $marks);
        $this->assertGreaterThan(500, $attendance);
        $this->assertGreaterThan(50, $invoices);

        // running it again must not create duplicates
        $before = $this->counts();
        $this->artisan('lms:demo-data', ['--force' => true])->assertExitCode(0);
        $this->assertSame($before, $this->counts());
    }

    public function test_demo_ebooks_are_real_pdfs_and_the_review_book_has_an_approval_chain(): void
    {
        $this->artisan('lms:demo-data', ['--force' => true])->assertExitCode(0);

        $ebook = Book::where('name', 'Clean Code')->firstOrFail();
        $this->assertTrue($ebook->is_ebook);
        $this->assertStringStartsWith('%PDF-1.4', Storage::disk('local')->get($ebook->ebook_path));

        $review = Book::where('name', 'Information Security Policy')->firstOrFail();
        $this->assertSame('review', $review->state);
        $this->assertNotNull($review->currentApproval());
        $this->assertSame('ICT head sign-off', Book::where('name', 'Information Security Policy')->first()->approvals()->where('sequence', 2)->value('title'));
    }

    public function test_demo_students_and_teachers_can_sign_in_to_their_portals(): void
    {
        $this->artisan('lms:demo-data', ['--force' => true])->assertExitCode(0);

        $student = User::where('email', 'abel.student@demo.test')->firstOrFail();
        $this->actingAs($student)->get('/student')->assertOk();
        $this->actingAs($student)->get('/student/library')->assertOk()->assertSee('Python Crash Course');
        $this->actingAs($student)->get('/student/results')->assertOk()->assertSee('Mid-term');

        $teacher = User::where('email', 'tigist.teacher@demo.test')->firstOrFail();
        $this->actingAs($teacher)->get('/teacher')->assertOk();
    }

    public function test_options_work(): void
    {
        $this->artisan('lms:demo-data', ['--list' => true])->assertExitCode(0);
        $this->artisan('lms:demo-data', ['--only' => 'nonsense', '--force' => true])->assertExitCode(1);

        $this->artisan('lms:demo-data', ['--only' => 'users,subjects', '--force' => true])->assertExitCode(0);
        $this->assertSame(0, Book::where('name', 'Clean Code')->count());     // books were not requested
        $this->assertNotNull(User::where('email', 'hana.clerk@demo.test')->first());
    }
}
