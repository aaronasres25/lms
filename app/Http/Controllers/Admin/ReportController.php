<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Department;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** lms.report (SQL view) + the consolidated QWeb report. */
class ReportController extends Controller
{
    private function query(Request $request)
    {
        $q = Loan::with(['book.department', 'member'])->whereNotNull('date_expected');

        if ($request->filled('department_id')) {
            $dept = Department::find($request->query('department_id'));
            if ($dept) $q->whereHas('book', fn ($b) => $b->whereIn('department_id', $dept->descendantIds()));
        }
        if ($request->filled('loan_state')) $q->where('state', $request->query('loan_state'));
        if ($request->filled('book_state')) $q->whereHas('book', fn ($b) => $b->where('state', $request->query('book_state')));
        if ($request->filled('from')) $q->whereDate('loan_date', '>=', $request->query('from'));
        if ($request->filled('to')) $q->whereDate('loan_date', '<=', $request->query('to'));
        if ($s = trim((string) $request->query('q', ''))) {
            $q->where(fn ($w) => $w->whereHas('book', fn ($b) => $b->where('name', 'like', "%{$s}%"))
                ->orWhereHas('member', fn ($m) => $m->where('name', 'like', "%{$s}%")));
        }
        return $q->latest('loan_date');
    }

    public function index(Request $request)
    {
        $q = $this->query($request);
        return view('admin.reports.index', [
            'rows'        => (clone $q)->paginate($this->perPage($request, 25))->withQueryString(),
            'totalFine'   => (clone $q)->sum('fine_amount'),
            'departments' => Department::options(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->query($request);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Loan', 'Book', 'Member', 'Department', 'Loan Date', 'Expected', 'Returned', 'Loan State', 'Book State', 'Overdue Days', 'Fine']);
            $query->chunk(500, function ($loans) use ($out) {
                foreach ($loans as $l) {
                    fputcsv($out, [
                        $l->name, $l->book->name, $l->member->name, $l->book->department->name ?? '',
                        $l->loan_date?->toDateString(), $l->date_expected?->toDateString(), $l->date_returned?->toDateString(),
                        $l->state, $l->book->state, $l->report_overdue_days, number_format($l->fine_amount, 2, '.', ''),
                    ]);
                }
            });
            fclose($out);
        }, 'library-report-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** Printable consolidated report (use the browser's "Save as PDF"). */
    public function summary()
    {
        $books = Book::all();
        $loans = Loan::all();
        $members = Member::all();

        return view('admin.reports.summary', [
            'today'       => today(),
            'bookStats'   => [
                'Total titles' => $books->count(),
                'Released' => $books->where('state', 'released')->count(),
                'Approved' => $books->where('state', 'approved')->count(),
                'Draft' => $books->where('state', 'draft')->count(),
                'Obsolete' => $books->where('state', 'obsolete')->count(),
                'E-books' => $books->where('is_ebook', true)->count(),
                'Expired' => $books->filter->is_expired->count(),
            ],
            'loanStats'   => [
                'Total loans' => $loans->count(),
                'Issued' => $loans->where('state', 'issued')->count(),
                'Returned' => $loans->where('state', 'returned')->count(),
                'Lost' => $loans->where('state', 'lost')->count(),
                'Overdue' => $loans->where('state', 'issued')->where('overdue_days', '>', 0)->count(),
                'Total fines' => number_format($loans->sum('fine_amount'), 2),
            ],
            'memberStats' => [
                'Total members' => $members->count(),
                'Active' => $members->filter->active->count(),
                'Inactive' => $members->reject->active->count(),
            ],
            'departments' => Department::with(['books' => fn ($q) => $q->withCount('issuedLoans')])->orderBy('name')->get()->map(fn ($d) => [
                'name' => $d->name, 'books' => $d->books->count(),
                'released' => $d->books->where('state', 'released')->count(),
                'issued' => $d->books->sum('issued_loans_count'),
            ]),
        ]);
    }
}
