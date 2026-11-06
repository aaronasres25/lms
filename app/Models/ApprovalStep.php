<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** One step of an approval flow template (per department, or the default flow when department_id is null). */
class ApprovalStep extends Model
{
    public const ROLES = ['user', 'qa', 'manager', 'admin'];

    protected $fillable = ['department_id', 'sequence', 'title', 'role', 'user_id'];

    public function department() { return $this->belongsTo(Department::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function getAssigneeLabelAttribute(): string
    {
        return $this->user ? $this->user->name : ucfirst((string) $this->role) . ' role';
    }

    /**
     * Steps that apply to a book: its own department's flow, else the nearest ancestor department's,
     * else the default flow, else the built-in single "QA review" step (the original Odoo behaviour).
     */
    public static function resolveFor(Book $book): Collection
    {
        $deptId = $book->department_id;
        $guard = 0;
        while ($deptId && $guard++ < 20) {
            $steps = self::where('department_id', $deptId)->orderBy('sequence')->get();
            if ($steps->isNotEmpty()) return $steps;
            $deptId = Department::where('id', $deptId)->value('parent_id');
        }

        $default = self::whereNull('department_id')->orderBy('sequence')->get();
        if ($default->isNotEmpty()) return $default;

        return collect([new self(['sequence' => 1, 'title' => 'QA review', 'role' => 'qa'])]);
    }

    /** Re-number a scope's steps 1..n in their current order. */
    public static function resequence(?int $departmentId): void
    {
        $i = 1;
        foreach (self::where('department_id', $departmentId)->orderBy('sequence')->orderBy('id')->get() as $step) {
            if ($step->sequence !== $i) $step->update(['sequence' => $i]);
            $i++;
        }
    }
}
