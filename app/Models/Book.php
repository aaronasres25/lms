<?php

namespace App\Models;

use App\Exceptions\LmsException;
use App\Mail\NewBookReleased;
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
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : asset('images/placeholder.jpg');
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

        $this->update(['state' => 'review']);
        $this->logActivity("Document submitted for review by {$by->name}. Reason: " . ($this->change_reason ?: 'Not specified'));
    }

    public function approve(User $by): void
    {
        self::requireRole($by, 'qa', 'Only Library QA can approve documents.');
        if ($this->state !== 'review') throw new LmsException('Only documents under review can be approved.');

        $this->update([
            'state'         => 'approved',
            'approval_date' => today(),
            'expiry_date'   => today()->addYears(config('lms.approval_valid_years', 2)),
        ]);
        $this->logActivity("Document approved by {$by->name}. Valid until {$this->expiry_date->toDateString()}.");
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
