<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Admin\MasterController;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Eloquent\Model;

class TermController extends MasterController
{
    protected function modelClass(): string { return Term::class; }
    protected function title(): string { return 'Term'; }
    protected function routePrefix(): string { return 'admin.school.terms'; }
    protected function permResource(): string { return 'academics'; }
    protected function orderColumn(): string { return 'start_date'; }
    protected function orderDirection(): string { return 'desc'; }
    protected function listQuery($query) { return $query->with('year'); }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name (Term 1)', 'type' => 'text', 'required' => true],
            ['name' => 'academic_year_id', 'label' => 'Academic year', 'type' => 'select', 'required' => true, 'options' => AcademicYear::orderByDesc('start_date')->pluck('name', 'id')->all()],
            ['name' => 'start_date', 'label' => 'Starts', 'type' => 'date'],
            ['name' => 'end_date', 'label' => 'Ends', 'type' => 'date'],
        ];
    }

    protected function rules(?Model $record): array
    {
        return ['name' => ['required', 'string', 'max:50'], 'academic_year_id' => ['required', 'exists:academic_years,id'],
                'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date']];
    }

    protected function extraColumns(): array
    {
        return ['Year' => fn ($r) => $r->year->name ?? '', 'Starts' => fn ($r) => $r->start_date?->toDateString(), 'Ends' => fn ($r) => $r->end_date?->toDateString()];
    }
}
