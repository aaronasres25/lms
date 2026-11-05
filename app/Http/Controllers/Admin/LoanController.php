<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\LmsException;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Loan::with(['book', 'member']);

        if ($s = trim((string) $request->query('q', ''))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                ->orWhereHas('book', fn ($b) => $b->where('name', 'like', "%{$s}%"))
                ->orWhereHas('member', fn ($m) => $m->where('name', 'like', "%{$s}%")));
        }
        if ($request->filled('state')) $query->where('state', $request->query('state'));
        if ($request->boolean('overdue')) {
            $query->where('state', 'issued')->whereNotNull('date_expected')->where('date_expected', '<', today()->toDateString());
        }

        return view('admin.loans.index', ['loans' => $query->latest('id')->paginate($this->perPage($request))->withQueryString()]);
    }

    public function create(Request $request)
    {
        return view('admin.loans.form', $this->formData($request));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'book_id'       => ['required', 'exists:books,id'],
            'member_id'     => ['nullable', 'exists:members,id'],
            'date_expected' => ['nullable', 'date'],
            'issue_now'     => ['nullable', 'boolean'],
        ]);

        // default member = the signed-in user's own membership (Odoo _default_member)
        $memberId = $data['member_id'] ?? $request->user()->member?->id;
        if (! $memberId) throw new LmsException('Please choose a member.');

        $loanDate = today();
        if (! empty($data['date_expected']) && $loanDate->diffInDays(\Carbon\Carbon::parse($data['date_expected']), false) > config('lms.loan_period_days', 14)) {
            throw new LmsException('Loan period cannot exceed ' . config('lms.loan_period_days', 14) . ' days.');
        }

        $loan = Loan::create([
            'book_id' => $data['book_id'], 'member_id' => $memberId,
            'loan_date' => $loanDate, 'date_expected' => $data['date_expected'] ?? null, 'state' => 'draft',
        ]);

        if ($request->boolean('issue_now')) {
            $loan->issue($request->user());
            return redirect()->route('admin.loans.show', $loan)->with('success', "Loan {$loan->name} created and book issued.");
        }
        return redirect()->route('admin.loans.show', $loan)->with('success', "Draft loan {$loan->name} created.");
    }

    public function show(Loan $loan)
    {
        $loan->load(['book', 'member', 'issuer', 'returner', 'activities.user']);
        return view('admin.loans.show', ['loan' => $loan]);
    }

    public function destroy(Loan $loan)
    {
        if ($loan->state === 'issued') throw new LmsException('An issued loan cannot be deleted - return it or mark it lost first.');
        $loan->delete();
        return redirect()->route('admin.loans.index')->with('success', 'Loan deleted.');
    }

    public function issue(Request $request, Loan $loan)
    {
        $loan->issue($request->user());
        return back()->with('success', 'Book issued.');
    }

    public function return(Request $request, Loan $loan)
    {
        $loan->markReturned($request->user());
        return back()->with('success', 'Book returned.');
    }

    public function lost(Request $request, Loan $loan)
    {
        $loan->markLost($request->user());
        return back()->with('success', 'Book marked as lost - inventory reduced.');
    }

    private function formData(Request $request): array
    {
        $books = Book::released()->withCount('issuedLoans')->orderBy('name')->get()
            ->filter(fn ($b) => $b->copies_available > 0);
        return [
            'books'     => $books,
            'members'   => Member::orderBy('name')->get()->filter->active,
            'preselect' => $request->query('book_id'),
            'myMember'  => $request->user()->member?->id,
        ];
    }
}
