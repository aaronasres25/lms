<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['user_id', 'admission_no', 'name', 'gender', 'date_of_birth', 'phone', 'address', 'guardian_name',
        'guardian_phone', 'guardian_email', 'section_id', 'status', 'admitted_on'];
    protected $casts = ['date_of_birth' => 'date', 'admitted_on' => 'date'];

    public function user() { return $this->belongsTo(User::class); }
    public function section() { return $this->belongsTo(Section::class); }
    public function attendances() { return $this->hasMany(Attendance::class); }
    public function marks() { return $this->hasMany(ExamMark::class); }
    public function invoices() { return $this->hasMany(FeeInvoice::class); }
    public function enrollments() { return $this->hasMany(Enrollment::class); }

    /** Library membership (exists when the student has a login). */
    public function libraryMember(): ?Member
    {
        return $this->user?->member;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /** @return array{present:int,absent:int,late:int,excused:int,total:int,percent:float} */
    public function attendanceSummary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $q = $this->attendances();
        if ($from) $q->whereDate('date', '>=', $from->toDateString());
        if ($to) $q->whereDate('date', '<=', $to->toDateString());
        $counts = $q->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $out = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
        foreach ($out as $k => $_) $out[$k] = (int) ($counts[$k] ?? 0);
        $out['total'] = array_sum($out);
        $out['percent'] = $out['total'] ? round(($out['present'] + $out['late']) / $out['total'] * 100, 1) : 0.0;
        return $out;
    }
}
