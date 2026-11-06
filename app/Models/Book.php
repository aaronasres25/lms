<?php

namespace App\Models;

use App\Exceptions\LmsException;
use App\Mail\NewBookReleased;
use App\Support\Notify;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Book extends Model
{
    use LogsActivity;

    public const STATES = [
        'draft' => 'Draft', 'review' => 'Under Review', 'approved' => 'Approved',
        'released' => 'Released', 'obsolete' => 'Obsolete',
    ];

    /** Fields that need a "Change Reason" once the document is Approved. */
    public const CONTROLLED_FIELDS = ['name', 'description', 'category_id', 'ebook_path', 'cover_path', 'is_ebook'];

    /** Reading statistics may change even on Released documents. */
    public const STATS_FIELDS = ['ebook_read_count', 'last_opened', 'total_pages'];

    protected $fillable = [
        'name', 'isbn', 'author_id', 'publication_year', 'category_id', 'department_id', 'description',
        'copies_total', 'fine_per_day', 'active', 'state', 'approval_date', 'expiry_date', 'version',
        'change_reason', 'is_ebook', 'ebook_path', 'ebook_filename', 'cover_path', 'is_secure_ebook',
        'ebook_read_count', 'total_pages', 'last_opened',
    ];

    protected $casts = [
        'approval_date' => 'date', 'expiry_date' => 'date', 'last_opened' => 'datetime',
        'active' => 'boolean', 'is_ebook' => 'boolean', 'is_secure_ebook' => 'boolean',
        'fine_per_day' => 'float', 'publication_year' => 'integer', 'copies_total' => 'integer',
    ];

    protected static function booted(): void
    {
        // Auto-generate a 13-digit ISBN (978 + 10-digit sequence) when none is supplied
        static::creating(function (Book $book) {
            if (! $book->isbn) {
                $book->isbn = self::nextIsbn();
            }
        });
    }

    /* ----------------------------------------------------------------- relations */

    public function author() { return $this->belongsTo(Author::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function loans() { return $this->hasMany(Loan::class); }
    public function issuedLoans() { return $this->hasMany(Loan::class)->where('state', 'issued'); }
    public function versionHistory() { return $this->hasMany(BookVersionHistory::class)->latest('id'); }
    public function readHistories() { return $this->hasMany(ReadHistory::class); }
    public function approvals() { return $this->hasMany(BookApproval::class); }
    public function notifiedUsers() { return $this->belongsToMany(User::class, 'book_notifications')->withTimestamps(); }

    /* ----------------------------------------------------------------- scopes / accessors */

    /** Visible in the public catalog: released, active and not expired. */
    public function scopeReleased($q)
    {
        return $q->where('state', 'released')->where('active', true)
            ->where(fn ($w) => $w->whereNull('expiry_date')->orWhere('expiry_date', '>=', today()->toDateString()));
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->lt(today());
    }

    public function getIssuedCountAttribute(): int
    {
        return $this->attributes['issued_loans_count'] ?? $this->issuedLoans()->count();
    }

    public function getCopiesAvailableAttribute(): int
    {
        return max($this->copies_total - $this->issued_count, 0);
    }

    public function getStateLabelAttribute(): string
    {
        return self::STATES[$this->state] ?? $this->state;
    }

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_path) return Storage::disk('public')->url($this->cover_path);
        if (file_exists(public_path('images/placeholder.jpg'))) return asset('images/placeholder.jpg');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 40"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
             . '<stop offset="0" stop-color="#7c3aed"/><stop offset="1" stop-color="#3b0f8c"/></linearGradient></defs><rect width="30" height="40" fill="url(#g)"/></svg>';
        return 'data:image/svg+xml,' . rawurlencode($svg);
    }

    /* ----------------------------------------------------------------- helpers */

    public static function nextIsbn(): string
    {
        do {
            $isbn = '978' . str_pad((string) Sequence::next('book_isbn'), 10, '0', STR_PAD_LEFT);
        } while (self::where('isbn', $isbn)->exists());
        return $isbn;
    }

    public function missingMandatory(): array
    {
        $missing = [];
        foreach (['name' => 'Name', 'isbn' => 'ISBN', 'author_id' => 'Author',
                  'publication_year' => 'Publication Year', 'category_id' => 'Category'] as $field => $label) {
            if (! $this->{$field}) $missing[] = $label;
        }
        return $missing;
    }

    private static function requireRole(User $user, string $role, string $message): void
    {
        if (! $user->atLeast($role)) throw new LmsException($message);
    }

    /* ----------------------------------------------------------------- guarded update (write() override) */

    /**
     * Applies the Odoo write() rules:
     *  - ISBN is immutable
     *  - Released documents are locked (administrators may still edit)
     *  - Approved documents need a Change Reason when controlled fields are modified
     */
    public function updateGuarded(array $data, User $user): void
    {
        if (array_key_exists('isbn', $data) && $data['isbn'] !== $this->isbn) {
            throw new LmsException('ISBN cannot be modified after creation.');
        }
        unset($data['isbn'], $data['state']);

        $this->fill($data);
        $changed = array_keys($this->getDirty());
        $contentChanged = array_diff($changed, self::STATS_FIELDS);

        if ($contentChanged) {
            if ($this->state === 'released' && ! $user->atLeast('admin')) {
                throw new LmsException('Released documents cannot be modified.');
            }
            if ($this->state === 'approved' && array_intersect($contentChanged, self::CONTROLLED_FIELDS)) {
                $reason = $data['change_reason'] ?? $this->getOriginal('change_reason');
                if (! $reason) {
                    throw new LmsException('Change Reason is mandatory when modifying an approved document.');
                }
            }
        }

        $this->save();

        if ($this->state === 'approved' && $contentChanged) {
            $this->logActivity("Approved document modified by {$user->name}. Reason: " . ($this->change_reason ?: 'n/a'));
        }
    }

    /* ----------------------------------------------------------------- lifecycle */

    public function submitForReview(User $by): void
    {
        self::requireRole($by, 'qa', 'Only Library QA can submit to review documents.');
        if ($this->state !== 'draft') throw new LmsException('Only draft documents can be submitted for review.');

        if ($missing = $this->missingMandatory()) {
            throw new LmsException("Cannot submit for review. Please fill the mandatory fields: " . implode(', ', $missing));
        }

        DB::transaction(function () use ($by) {
            // snapshot the approval flow that applies to this department
            $cycle = ((int) BookApproval::where('book_id', $this->id)->max('cycle')) + 1;
            foreach (ApprovalStep::resolveFor($this)->values() as $i => $step) {
                BookApproval::create([
                    'book_id' => $this->id, 'cycle' => $cycle, 'sequence' => $step->sequence, 'title' => $step->title,
                    'role' => $step->role, 'user_id' => $step->user_id, 'status' => 'pending',
                    'is_current' => $i === 0, 'requested_by' => $by->id,
                ]);
            }
            $this->update(['state' => 'review']);
        });

        $first = $this->currentApproval();
        $this->logActivity("Submitted for review by {$by->name}. Reason: " . ($this->change_reason ?: 'Not specified')
            . ($first ? ". Waiting for {$first->assignee_label} (step {$first->sequence}: {$first->title})." : ''));
        if ($first) $this->notifyTurn($first, $by);
    }

    /** The step whose turn it is (null when the document is not under review). */
    public function currentApproval(): ?BookApproval
    {
        return BookApproval::where('book_id', $this->id)->current()->orderBy('sequence')->first();
    }

    /** Approve button rule: only the assigned approver of the CURRENT step (or an admin when override is on). */
    public function canBeApprovedBy(User $user): bool
    {
        if (! $user->active || $this->state !== 'review') return false;
        $step = $this->currentApproval();
        if (! $step) return false;

        return $step->isAssignedTo($user) || ($user->role === 'admin' && config('lms.approval_admin_override', true));
    }

    public function approve(User $by, ?string $comment = null): void
    {
        if ($this->state !== 'review') throw new LmsException('Only documents under review can be approved.');
        $step = $this->currentApproval();
        if (! $step) throw new LmsException('There is no pending approval step for this document.');

        if (! $this->canBeApprovedBy($by)) {
            throw new LmsException("It is not your turn. Waiting for {$step->assignee_label} (step {$step->sequence}: {$step->title}).");
        }
        $override = ! $step->isAssignedTo($by);

        $next = null;
        DB::transaction(function () use ($by, $comment, $step, &$next) {
            $step->update(['status' => 'approved', 'is_current' => false, 'acted_by' => $by->id, 'acted_at' => now(), 'comment' => $comment]);

            $next = BookApproval::where('book_id', $this->id)->where('cycle', $step->cycle)
                ->where('status', 'pending')->orderBy('sequence')->first();

            if ($next) {
                $next->update(['is_current' => true]);
            } else {
                $this->update([
                    'state'         => 'approved',
                    'approval_date' => today(),
                    'expiry_date'   => today()->addYears(config('lms.approval_valid_years', 2)),
                ]);
            }
        });

        $note = $override ? ' (admin override)' : '';
        $requesterId = $step->requested_by;

        if ($next) {
            $this->logActivity("Step {$step->sequence} ({$step->title}) approved by {$by->name}{$note}. Next: {$next->assignee_label} (step {$next->sequence}: {$next->title}).");
            $this->notifyTurn($next, User::find($requesterId));
        } else {
            $this->logActivity("Final approval by {$by->name}{$note}. Document approved, valid until {$this->expiry_date->toDateString()}.");
            $url = route('admin.books.show', $this, false);
            if ($requesterId && ($requester = User::find($requesterId))) {
                Notify::send($requester, 'Document approved', "\"{$this->name}\" passed every approval step.", $url, 'check', 'green', ['book_id' => $this->id]);
            }
            Notify::send(User::staff()->whereIn('role', ['manager', 'admin'])->get(), 'Ready to release',
                "\"{$this->name}\" is fully approved and can be released.", $url, 'shield', 'green', ['book_id' => $this->id]);
        }
    }

    public function reject(User $by, string $comment): void
    {
        if (trim($comment) === '') throw new LmsException('Please give a reason for rejecting the document.');
        if ($this->state !== 'review') throw new LmsException('Only documents under review can be rejected.');
        $step = $this->currentApproval();
        if (! $step) throw new LmsException('There is no pending approval step for this document.');

        if (! $this->canBeApprovedBy($by)) {
            throw new LmsException("It is not your turn. Waiting for {$step->assignee_label} (step {$step->sequence}: {$step->title}).");
        }

        DB::transaction(function () use ($by, $comment, $step) {
            $step->update(['status' => 'rejected', 'is_current' => false, 'acted_by' => $by->id, 'acted_at' => now(), 'comment' => $comment]);
            BookApproval::where('book_id', $this->id)->where('cycle', $step->cycle)->where('status', 'pending')->update(['status' => 'skipped']);
            $this->update(['state' => 'draft', 'change_reason' => "Rejected at step {$step->sequence} ({$step->title}): {$comment}"]);
        });

        $this->logActivity("Rejected by {$by->name} at step {$step->sequence} ({$step->title}). Reason: {$comment}");
        if ($step->requested_by && ($requester = User::find($step->requested_by))) {
            Notify::send($requester, 'Document rejected', "\"{$this->name}\" was rejected: {$comment}",
                route('admin.books.show', $this, false), 'x', 'red', ['book_id' => $this->id]);
        }
    }

    private function notifyTurn(BookApproval $step, ?User $requester): void
    {
        Notify::send($step->assignees(), 'Approval needed',
            "\"{$this->name}\" is waiting for your approval (step {$step->sequence}: {$step->title})"
            . ($requester ? ", requested by {$requester->name}." : '.'),
            route('admin.books.show', $this, false), 'shield', 'amber', ['book_id' => $this->id]);
    }

    public function release(User $by): void
    {
        self::requireRole($by, 'manager', 'Only Library Managers or Admins can release documents.');
        if ($this->state !== 'approved') throw new LmsException('Only approved documents can be released.');

        $this->update(['state' => 'released']);

        // Notify staff by e-mail and keep the "Notification Tracking" list
        $users = User::staff()->whereNotNull('email')->get();
        foreach ($users as $user) {
            try {
                Mail::to($user->email)->queue(new NewBookReleased($this, $user));
            } catch (\Throwable $e) {
                Log::warning("LMS: release e-mail to {$user->email} failed: {$e->getMessage()}");
            }
        }
        $this->notifiedUsers()->sync($users->pluck('id')->all());
        Notify::send($users->filter(fn ($u) => $u->canDo('books', 'read')), 'New document released',
            "\"{$this->name}\" (v{$this->version}) is now available.", route('admin.books.show', $this, false), 'book', 'violet', ['book_id' => $this->id]);
        $this->logActivity("Document released by {$by->name}. Notifications queued for {$users->count()} users.");
    }

    public function markObsolete(User $by): void
    {
        self::requireRole($by, 'manager', 'Only Library Managers can mark documents obsolete.');
        if ($this->state !== 'released') throw new LmsException('Only released documents can be marked obsolete.');

        $this->update(['state' => 'obsolete', 'active' => false]);
        $this->logActivity("Document marked obsolete by {$by->name}.");
    }

    /* ----------------------------------------------------------------- versioning */

    /** Minor (1.0 -> 1.1) or major (1.0 -> 2.0) version. Returns the new draft record. */
    public function createNewVersion(User $by, string $reason, bool $major = false): self
    {
        if ($this->state !== 'released') throw new LmsException('New versions can only be created from Released books.');
        if (trim($reason) === '') throw new LmsException('Please provide a Change Reason before creating a new version.');
        if (! preg_match('/^(\d+)\.(\d+)$/', (string) $this->version, $m)) throw new LmsException('Invalid version format. Use X.Y');

        $newVersion = $major ? ((int) $m[1] + 1) . '.0' : $m[1] . '.' . ((int) $m[2] + 1);

        return DB::transaction(function () use ($by, $reason, $major, $newVersion) {
            $copy = $this->replicate([
                'isbn', 'state', 'approval_date', 'expiry_date', 'ebook_read_count', 'last_opened',
                'change_reason', 'ebook_path', 'cover_path',
            ]);
            $copy->version = $newVersion;
            $copy->state = 'draft';
            $copy->active = true;
            $copy->ebook_read_count = 0;
            $copy->ebook_path = $this->duplicateFile($this->ebook_path, 'local');
            $copy->cover_path = $this->duplicateFile($this->cover_path, 'public');
            $copy->save();                                  // ISBN generated in the creating hook

            BookVersionHistory::create([
                'book_id' => $this->id, 'version' => $newVersion, 'change_reason' => $reason, 'user_id' => $by->id,
            ]);

            $this->forceFill(['change_reason' => $reason, 'state' => 'obsolete', 'active' => false])->save();

            $kind = $major ? 'MAJOR version' : 'version';
            $this->logActivity("New {$kind} {$newVersion} created by {$by->name}. Reason: {$reason}");
            $copy->logActivity("Created as {$kind} {$newVersion} of ISBN {$this->isbn}. Reason: {$reason}");

            return $copy;
        });
    }

    private function duplicateFile(?string $path, string $disk): ?string
    {
        if (! $path || ! Storage::disk($disk)->exists($path)) return null;
        $dir = trim(dirname($path), '.');
        $new = ($dir !== '' ? $dir . '/' : '') . Str::uuid() . '.' . pathinfo($path, PATHINFO_EXTENSION);
        Storage::disk($disk)->copy($path, $new);
        return $new;
    }

    /* ----------------------------------------------------------------- e-book */

    /** null = allowed. Staff may preview any state (QA must read before approving). */
    public function readAccessError(?User $user): ?string
    {
        if (! $this->is_ebook || ! $this->ebook_path) return 'No e-book is available for this title.';
        if ($user && $user->isStaff()) return null;
        if ($this->state !== 'released' || ! $this->active) return 'This document is not available.';
        if ($this->is_expired) return 'This document has expired.';
        return null;
    }

    /** Called on every viewer open (read counter + history for the dashboard charts). */
    public function registerRead(?User $user): void
    {
        $this->increment('ebook_read_count', 1, ['last_opened' => now()]);
        ReadHistory::create([
            'user_id'       => $user?->id,
            'book_id'       => $this->id,
            'category_id'   => $this->category_id,
            'department_id' => $this->department_id,
        ]);
    }
}
