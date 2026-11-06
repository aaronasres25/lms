<?php

namespace App\Http\Controllers\Admin\School;

use App\Exceptions\LmsException;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeType;
use App\Models\Student;
use App\Models\Term;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = FeeInvoice::with(['student.section.gradeLevel'])->withSum('payments', 'amount');
        if ($s = trim((string) $request->query('q', ''))) {
            $q->where(fn ($w) => $w->where('number', 'like', "%{$s}%")->orWhereHas('student', fn ($st) => $st->where('name', 'like', "%{$s}%")->orWhere('admission_no', 'like', "%{$s}%")));
        }
        if ($request->filled('status')) $q->where('status', $request->query('status'));
        if ($request->boolean('overdue')) $q->whereIn('status', ['unpaid', 'partial'])->whereNotNull('due_date')->where('due_date', '<', today()->toDateString());

        $totals = FeeInvoice::where('status', '!=', 'void')->selectRaw('sum(total) as billed')->first();
        $collected = (float) FeePayment::sum('amount');

        return view('admin.school.invoices.index', [
            'invoices' => $q->latest('id')->paginate($this->perPage($request))->withQueryString(),
            'billed' => (float) ($totals->billed ?? 0), 'collected' => $collected,
        ]);
    }

    public function create()
    {
        return view('admin.school.invoices.create', [
            'sections' => StudentController::sectionOptions(), 'feeTypes' => FeeType::orderBy('name')->get(),
            'terms' => Term::with('year')->orderByDesc('start_date')->get(), 'year' => AcademicYear::current(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'section_id' => ['nullable', 'exists:sections,id'], 'admission_no' => ['nullable', 'string', 'exists:students,admission_no'],
            'term_id' => ['nullable', 'exists:terms,id'], 'description' => ['nullable', 'string', 'max:255'], 'due_date' => ['nullable', 'date'],
            'fee_types' => ['required', 'array', 'min:1'], 'fee_types.*' => ['exists:fee_types,id'],
            'amounts' => ['required', 'array'], 'amounts.*' => ['nullable', 'numeric', 'min:0'],
        ], ['fee_types.required' => 'Tick at least one fee.']);

        if (empty($data['section_id']) && empty($data['admission_no'])) throw new LmsException('Choose a class, or enter one admission number.');

        $students = ! empty($data['admission_no'])
            ? Student::where('admission_no', $data['admission_no'])->get()
            : Student::where('section_id', $data['section_id'])->where('status', 'active')->get();
        if ($students->isEmpty()) throw new LmsException('No active students found for that selection.');

        $year = AcademicYear::current();
        if (! $year) throw new LmsException('Create an academic year first.');
        $types = FeeType::whereIn('id', $data['fee_types'])->get();
        $created = 0;

        DB::transaction(function () use ($students, $types, $data, $year, &$created) {
            foreach ($students as $student) {
                $items = [];
                foreach ($types as $t) {
                    $amount = (float) ($data['amounts'][$t->id] ?? $t->amount);
                    if ($amount > 0) $items[] = ['fee_type_id' => $t->id, 'description' => $t->name, 'amount' => $amount];
                }
                if (! $items) continue;

                $invoice = FeeInvoice::create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'term_id' => $data['term_id'] ?? null,
                    'description' => $data['description'] ?? null, 'total' => array_sum(array_column($items, 'amount')), 'due_date' => $data['due_date'] ?? null]);
                $invoice->items()->createMany($items);
                $created++;

                if ($student->user) {
                    Notify::send($student->user, 'New fee invoice', "{$invoice->number}: " . number_format($invoice->total, 2) . ' due' . ($invoice->due_date ? ' by ' . $invoice->due_date->toDateString() : '') . '.',
                        route('student.fees', [], false), 'chart', 'amber');
                }
            }
        });

        return redirect()->route('admin.school.invoices.index')->with('success', "{$created} invoice(s) created.");
    }

    public function show(FeeInvoice $invoice)
    {
        $invoice->load(['student.section.gradeLevel', 'year', 'term', 'items', 'payments.receiver']);
        return view('admin.school.invoices.show', ['invoice' => $invoice]);
    }

    public function pay(Request $request, FeeInvoice $invoice)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0'], 'paid_on' => ['required', 'date'],
            'method' => ['required', 'in:cash,bank,mobile,card'], 'reference' => ['nullable', 'string', 'max:100']]);

        if ($invoice->status === 'void') throw new LmsException('This invoice is void.');
        if ($data['amount'] > $invoice->balance + 0.0001) throw new LmsException('The payment is more than the balance (' . number_format($invoice->balance, 2) . ').');

        $invoice->payments()->create($data + ['received_by' => $request->user()->id]);
        $invoice->refreshStatus();

        return back()->with('success', 'Payment recorded.');
    }

    public function void(FeeInvoice $invoice)
    {
        if ($invoice->payments()->exists()) throw new LmsException('An invoice with payments cannot be voided.');
        $invoice->update(['status' => 'void']);
        return back()->with('success', 'Invoice voided.');
    }
}
