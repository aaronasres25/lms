<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'user_id', 'membership_start', 'membership_end', 'max_books'];
    protected $casts = ['membership_start' => 'date', 'membership_end' => 'date'];

    public function user() { return $this->belongsTo(User::class); }
    public function loans() { return $this->hasMany(Loan::class); }

    /** Membership expires when membership_end < today. */
    public function getActiveAttribute(): bool
    {
        return $this->membership_end && ! $this->membership_end->lt(today());
    }

    public function getLoanCountIssuedAttribute(): int { return $this->loans()->where('state', 'issued')->count(); }
    public function getLoanCountReturnedAttribute(): int { return $this->loans()->where('state', 'returned')->count(); }
    public function getLoanCountLostAttribute(): int { return $this->loans()->where('state', 'lost')->count(); }
}
