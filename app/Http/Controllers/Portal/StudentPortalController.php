<?php

namespace App\Http\Controllers\Portal;

use App\Exceptions\LmsException;
use App\Http\Controllers\Admin\School\TimetableController;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Book;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\FeeInvoice;
use App\Models\GradeScale;
use App\Models\Loan;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Models\Viewer;
use App\Support\Notify;
use Illuminate\Http\Request;

class StudentPortalController extends Controller
{
    private function student(Request $request)
    {
        return $request->user()->student;
    }

    public function dashboard(Request $request)
    {
        $student = $this->student($request);
        $member = $request->user()->member;

        return view('portal.student-dashboard', [
            'student'    => $student?->load(['section.gradeLevel', 'section.classTeacher']),
            'today'      => $student?->section_id ? TimetableSlot::with(['subject', 'teacher'])->where('section_id', $student->section_id)->where('day_of_week', today()->dayOfWeekIso)->orderBy('period_no')->get() : collect(),
            'attendance' => $student?->attendanceSummary(now()->subDays(30), now()),
            'due'        => $student ? FeeInvoice::withSum('payments', 'amount')->where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial'])->get()->sum(fn ($i) => $i->balance) : 0,
            'loans'      => $member ? Loan::with('book')->where('member_id', $member->id)->whereIn('state', ['draft', 'issued'])->latest('id')->get() : collect(),
            'reading'    => Viewer::with('book.author')->where('user_id', $request->user()->id)->whereHas('book', fn ($b) => $b->released())->latest('last_read_at')->limit(4)->get(),
            'notices'    => Announcement::for('students')->latest('id')->limit(5)->get(),
        ]);
    }

    public function timetable(Request $request)
    {
        $student = $this->student($request);
        $slots = $student?->section_id ? TimetableSlot::with(['subject', 'teacher'])->where('section_id', $student->section_id)->get() : collect();
        return view('portal.student-timetable', ['grid' => TimetableController::grid($slots), 'student' => $student?->load('section.gradeLevel')]);
    }

    public function attendance(Request $request)
    {
        $student = $this->student($request);
        return view('portal.student-attendance', [
            'student' => $student,
            'summary' => $student?->attendanceSummary(now()->subDays(90), now()),
            'records' => $student ? $student->attendances()->latest('date')->limit(60)->get() : collect(),
        ]);
    }

    public function results(Request $request)
    {
        $student = $this->student($request);
        $exams = $student ? Exam::with(['year', 'term'])->where('is_published', true)->whereHas('marks', fn ($m) => $m->where('student_id', $student->id))->orderByDesc('id')->get() : collect();

        $summary = $exams->mapWithKeys(function ($e) use ($student) {
            $marks = ExamMark::where('exam_id', $e->id)->where('student_id', $student->id)->whereNotNull('marks')->get();
            $avg = $marks->count() ? round($marks->sum('marks') / ($marks->count() * $e->max_marks) * 100, 1) : null;
            return [$e->id => ['avg' => $avg, 'grade' => GradeScale::gradeFor($avg)[0], 'subjects' => $marks->count()]];
        });

        return view('portal.student-results', ['student' => $student, 'exams' => $exams, 'summary' => $summary]);
    }

    public function reportCard(Request $request, Exam $exam)
    {
        $student = $this->student($request);
        abort_unless($student, 404);
        return app(\App\Http\Controllers\Admin\School\ReportCardController::class)->show($request, $student, $exam);
    }

    public function fees(Request $request)
    {
        $student = $this->student($request);
        return view('portal.student-fees', [
            'student'  => $student,
            'invoices' => $student ? FeeInvoice::with(['items', 'payments'])->withSum('payments', 'amount')->where('student_id', $student->id)->where('status', '!=', 'void')->latest('id')->get() : collect(),
        ]);
    }

    /* ------------------------------------------------------------ library */

    public function library(Request $request)
    {
        $member = $request->user()->member;
        $q = Book::released()->with('author')->withCount('issuedLoans');
        if ($s = trim((string) $request->query('q', ''))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhereHas('author', fn ($a) => $a->where('name', 'like', "%{$s}%")));
        }
        if ($request->query('type') === 'physical') $q->where('is_ebook', false);
        if ($request->query('type') === 'ebook') $q->where('is_ebook', true);

        $loans = $member ? Loan::with('book')->where('member_id', $member->id)->latest('id')->limit(30)->get() : collect();

        return view('portal.student-library', [
            'books'   => $q->orderBy('name')->paginate(12)->withQueryString(),
            'member'  => $member,
            'loans'   => $loans,
            'openIds' => $loans->whereIn('state', ['draft', 'issued'])->pluck('book_id')->all(),
            'fines'   => (float) $loans->sum('fine_amount'),
        ]);
    }

    /** Student asks for a physical book -> a draft loan the librarian issues. */
    public function requestLoan(Request $request, Book $book)
    {
        $user = $request->user();
        $member = $user->member;

        if (! $member) throw new LmsException('Your library membership is not set up yet. Please ask the library.');
        if (! $member->active) throw new LmsException('Your library membership has expired. Please contact the library.');
        if (! Book::released()->whereKey($book->id)->exists()) throw new LmsException('This book is not available.');
        if ($book->copies_total < 1) throw new LmsException('This title is e-book only - open it from the catalog to read it online.');
        if ($book->copies_available < 1) throw new LmsException('No copies are available right now. Please try again later.');

        $open = Loan::where('member_id', $member->id)->whereIn('state', ['draft', 'issued']);
        if ((clone $open)->where('book_id', $book->id)->exists()) throw new LmsException('You already requested or borrowed this book.');
        if ((clone $open)->count() >= $member->max_books) throw new LmsException("You can have at most {$member->max_books} books at a time.");

        $loan = Loan::create(['book_id' => $book->id, 'member_id' => $member->id, 'loan_date' => today(), 'state' => 'draft']);

        Notify::send(User::staff()->get()->filter(fn ($u) => $u->canDo('loans', 'update')), 'Book request',
            "{$user->name} requested \"{$book->name}\".", route('admin.loans.show', $loan, false), 'loan', 'violet', ['loan_id' => $loan->id]);

        return back()->with('success', "Request sent for \"{$book->name}\". The library will issue it to you.");
    }

    public function cancelRequest(Request $request, Loan $loan)
    {
        $member = $request->user()->member;
        abort_unless($member && $loan->member_id === $member->id && $loan->state === 'draft', 403);
        $loan->delete();
        return back()->with('success', 'Request cancelled.');
    }
}
