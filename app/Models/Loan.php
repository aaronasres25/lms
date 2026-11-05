<?php

namespace App\Models;

use App\Exceptions\LmsException;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Loan extends Model
{
    use LogsActivity;

    public const STATES = ['draft' => 'Draft', 'issued' => 'Issued', 'returned' => 'Returned', 'lost' => 'Lost'];

    protected $fillable = [
        'name', 'book_id', 'member_id', 'issued_by', 'returned_by', 'loan_date', 'date_expected',
        'date_returned', 'state', 'overdue_days', 'fine_amount',
    ];

    protected $casts = [
        'loan_date' => 'date', 'date_expected' => 'date', 'date_returned' => 'date', 'fine_amount' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (Loan $loan) {
            if (! $loan->name || $loan->name === 'New') {
                $loan->name = 'LN' . str_pad((string) Sequence::next('loan'), 5, '0', STR_PAD_LEFT);
            }
            $loan->loan_date ??= today();
            $loan->state ??= 'draft';
        });

        static::created(function (Loan $loan) {
            $loan->logActivity("Loan record created. Book: {$loan->book->name}. Member: {$loan->member->name}.");
        });
    }

    public function book() { return $this->belongsTo(Book::class); }
    public function member() { return $this->belongsTo(Member::class); }
    public function issuer() { return $this->belongsTo(User::class, 'issued_by'); }
    public function returner() { return $this->belongsTo(User::class, 'returned_by'); }

    public function getStateLabelAttribute(): string { return self::STATES[$this->state] ?? $this->state; }

    /** Overdue as shown in the Reports view (also for returned/lost loans). */
    public function getReportOverdueDaysAttribute(): int
    {
        if (! $this->date_expected) return 0;
        return max(self::daysBetween($this->date_expected, $this->date_returned ?? today()), 0);
    }

    private static function daysBetween(Carbon $from, Carbon $to): int
    {
        return (int) round(($to->copy()->startOfDay()->timestamp - $from->copy()->startOfDay()->timestamp) / 86400);
    }

    /* ----------------------------------------------------------------- fines */

    /** Computes + stores overdue_days and fine_amount (Odoo _compute_overdue). */
    public function recalculate(): void
    {
        $perDay = (float) ($this->book->fine_per_day ?? 0);
        $period = config('lms.loan_period_days', 14);
        $due = $this->date_expected ?? $this->loan_date?->copy()->addDays($period);
        $overdue = 0;
        $fine = 0.0;

        if ($this->loan_date && $due) {
            if ($this->state === 'issued') {
                $overdue = max(self::daysBetween($due, today()), 0);
                $fine = $overdue * $perDay;
            } elseif ($this->state === 'returned' && $this->date_returned) {
                $overdue = max(self::daysBetween($due, $this->date_returned), 0);
                $fine = $overdue * $perDay;
            } elseif ($this->state === 'lost') {
                $end = $this->date_returned ?? today();
                $total = self::daysBetween($this->loan_date, $end);
                $fine = max($total, 1) * $perDay * config('lms.lost_fine_multiplier', 5);
            }
        }

        $this->forceFill(['overdue_days' => $overdue, 'fine_amount' => round($fine, 2)])->saveQuietly();
    }

    /** Nightly job: refresh overdue/fine on every issued loan. */
    public static function recomputeIssued(): int
    {
        $n = 0;
        self::with('book')->where('state', 'issued')->chunkById(200, function ($loans) use (&$n) {
            foreach ($loans as $loan) { $loan->recalculate(); $n++; }
        });
        return $n;
    }

    /* ----------------------------------------------------------------- workflow */

    public function issue(User $by): void
    {
        if ($this->state !== 'draft') throw new LmsException('Only draft loans can be issued.');

        DB::transaction(function () use ($by) {
            // lock the book row so two concurrent issues cannot take the last copy
            $book = Book::lockForUpdate()->findOrFail($this->book_id);
            $member = $this->member;

            if ($book->state !== 'released') throw new LmsException('Only released books can be issued.');
            if ($book->is_expired) throw new LmsException('This book is expired and cannot be issued.');
            if (! $member->active) throw new LmsException('Membership expired. Cannot issue books.');

            if (self::where('member_id', $member->id)->where('book_id', $book->id)->where('state', 'issued')->exists()) {
                throw new LmsException('This member already has this book issued.');
            }
            if ($book->copies_available <= 0) throw new LmsException('No copies available for this book.');

            $out = self::where('member_id', $member->id)->where('state', 'issued')->count();
            if ($out >= $member->max_books) {
                throw new LmsException("Member has reached maximum issued books ({$member->max_books}).");
            }

            $today = today();
            $max = $today->copy()->addDays(config('lms.loan_period_days', 14));
            if ($this->date_expected && $this->date_expected->lt($today)) {
                throw new LmsException('Expected return date cannot be in the past.');
            }
            $expected = $this->date_expected ? $this->date_expected->copy()->min($max) : $max;

            $this->forceFill([
                'loan_date' => $today, 'date_expected' => $expected, 'state' => 'issued', 'issued_by' => $by->id,
            ])->save();
            $this->unsetRelation('book');
            $this->recalculate();

            $this->logActivity("Book issued: {$book->name} to {$member->name}. Expected return: {$expected->toDateString()}. Issued by {$by->name}.");
        });
    }

    public function markReturned(User $by): void
    {
        if ($this->state !== 'issued') throw new LmsException('Only issued books can be returned.');

        $this->forceFill(['date_returned' => today(), 'state' => 'returned', 'returned_by' => $by->id])->save();
        $this->recalculate();
        $this->logActivity("Book returned: {$this->book->name} by {$this->member->name}. Returned to {$by->name}. Fine: {$this->fine_amount}.");
    }

    public function markLost(User $by): void
    {
        if ($this->state !== 'issued') throw new LmsException('Only issued books can be marked as lost.');

        DB::transaction(function () use ($by) {
            $book = Book::lockForUpdate()->findOrFail($this->book_id);
            $book->forceFill(['copies_total' => max($book->copies_total - 1, 0)])->save();   // inventory reduced

            $this->forceFill(['state' => 'lost'])->save();
            $this->unsetRelation('book');
            $this->recalculate();
            $this->logActivity("Book marked as lost (inventory reduced): {$book->name}. Member: {$this->member->name}. Marked by {$by->name}. Fine: {$this->fine_amount}.");
        });
    }
}
