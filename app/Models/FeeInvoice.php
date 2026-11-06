<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeInvoice extends Model
{
    protected $fillable = ['number', 'student_id', 'academic_year_id', 'term_id', 'description', 'total', 'due_date', 'status'];
    protected $casts = ['total' => 'float', 'due_date' => 'date'];

    protected static function booted(): void
    {
        static::creating(function (FeeInvoice $i) {
            $i->number ??= 'INV' . str_pad((string) Sequence::next('invoice'), 5, '0', STR_PAD_LEFT);
        });
    }

    public function student() { return $this->belongsTo(Student::class); }
    public function year() { return $this->belongsTo(AcademicYear::class, 'academic_year_id'); }
    public function term() { return $this->belongsTo(Term::class); }
    public function items() { return $this->hasMany(FeeInvoiceItem::class); }
    public function payments() { return $this->hasMany(FeePayment::class); }

    public function getPaidAttribute(): float
    {
        return (float) ($this->attributes['payments_sum_amount'] ?? $this->payments()->sum('amount'));
    }

    public function getBalanceAttribute(): float
    {
        return round($this->total - $this->paid, 2);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid' && $this->status !== 'void' && $this->due_date && $this->due_date->lt(today());
    }

    public function refreshStatus(): void
    {
        if ($this->status === 'void') return;
        $paid = (float) $this->payments()->sum('amount');
        $this->update(['status' => $paid >= $this->total && $this->total > 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')]);
    }
}
