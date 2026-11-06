<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Admin\MasterController;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Term;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ExamController extends MasterController
{
    protected function modelClass(): string { return Exam::class; }
    protected function title(): string { return 'Exam'; }
    protected function routePrefix(): string { return 'admin.school.exams'; }
    protected function permResource(): string { return 'exams'; }
    protected function orderColumn(): string { return 'id'; }
    protected function orderDirection(): string { return 'desc'; }
    protected function listQuery($query) { return $query->with(['year', 'term']); }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name (Mid-term)', 'type' => 'text', 'required' => true],
            ['name' => 'academic_year_id', 'label' => 'Academic year', 'type' => 'select', 'required' => true, 'options' => AcademicYear::orderByDesc('start_date')->pluck('name', 'id')->all()],
            ['name' => 'term_id', 'label' => 'Term', 'type' => 'select', 'options' => Term::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'max_marks', 'label' => 'Maximum marks', 'type' => 'number', 'required' => true],
            ['name' => 'start_date', 'label' => 'Starts', 'type' => 'date'],
            ['name' => 'end_date', 'label' => 'Ends', 'type' => 'date'],
            ['name' => 'is_published', 'label' => 'Results visible to students', 'type' => 'checkbox'],
        ];
    }

    protected function rules(?Model $record): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'academic_year_id' => ['required', 'exists:academic_years,id'],
                'term_id' => ['nullable', 'exists:terms,id'], 'max_marks' => ['required', 'integer', 'between:1,1000'],
                'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'is_published' => ['nullable', 'boolean']];
    }

    protected function prepare(array &$data, Request $request): void
    {
        $data['is_published'] = $request->boolean('is_published');
        $data['term_id'] = $data['term_id'] ?? null;
    }

    protected function extraColumns(): array
    {
        return ['Year' => fn ($r) => $r->year->name ?? '', 'Term' => fn ($r) => $r->term->name ?? '', 'Max' => fn ($r) => $r->max_marks,
                'Results' => fn ($r) => $r->is_published ? '✔ published' : 'hidden'];
    }
}
