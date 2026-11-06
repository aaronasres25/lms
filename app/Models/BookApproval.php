<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** One step of the approval chain of one submission round of a book. */
class BookApproval extends Model
{
    protected $fillable = [
        'book_id', 'cycle', 'sequence', 'title', 'role', 'user_id', 'status', 'is_current',
        'requested_by', 'acted_by', 'acted_at', 'comment',
    ];

    protected $casts = ['is_current' => 'boolean', 'acted_at' => 'datetime'];

    public function book() { return $this->belongsTo(Book::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function actor() { return $this->belongsTo(User::class, 'acted_by'); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }

    public function getAssigneeLabelAttribute(): string
    {
        return $this->user ? $this->user->name : ucfirst((string) $this->role) . ' role';
    }

    /** Is this step assigned to the given user (a named person, or anyone holding the step's role)? */
    public function isAssignedTo(User $user): bool
    {
        if ($this->user_id) return (int) $this->user_id === (int) $user->id;
        return $this->role !== null && $user->role === $this->role;
    }

    /** People who should be told it is their turn. */
    public function assignees(): Collection
    {
        if ($this->user_id) return User::where('id', $this->user_id)->where('active', true)->get();
        return User::where('role', $this->role)->where('active', true)->get();
    }

    public function scopeCurrent($q)
    {
        return $q->where('is_current', true)->where('status', 'pending');
    }

    public function scopeAssignedTo($q, User $user)
    {
        return $q->where(fn ($w) => $w->where('user_id', $user->id)
            ->orWhere(fn ($x) => $x->whereNull('user_id')->where('role', $user->role)));
    }

    /** Books waiting for this user's approval right now (admins see all when override is on). */
    public static function queueFor(User $user)
    {
        $q = self::with(['book.department', 'requester'])->current()
            ->whereHas('book', fn ($b) => $b->where('state', 'review'));

        if (! ($user->role === 'admin' && config('lms.approval_admin_override', true))) {
            $q->assignedTo($user);
        }
        return $q;
    }
}
