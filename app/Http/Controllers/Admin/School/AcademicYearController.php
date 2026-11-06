<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Admin\MasterController;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AcademicYearController extends MasterController
{
    protected function modelClass(): string { return AcademicYear::class; }
    protected function title(): string { return 'Academic year'; }
    protected function routePrefix(): string { return 'admin.school.academic-years'; }
    protected function permResource(): string { return 'academics'; }
    protected function orderColumn(): string { return 'start_date'; }
    protected function orderDirection(): string { return 'desc'; }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name (e.g. 2026/2027)', 'type' => 'text', 'required' => true],
            ['name' => 'start_date', 'label' => 'Starts', 'type' => 'date', 'required' => true],
            ['name' => 'end_date', 'label' => 'Ends', 'type' => 'date', 'required' => true],
            ['name' => 'is_current', 'label' => 'Current year', 'type' => 'checkbox'],
        ];
    }

    protected function rules(?Model $record): array
    {
        return ['name' => ['required', 'string', 'max:50'], 'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after:start_date'], 'is_current' => ['nullable', 'boolean']];
    }

    protected function prepare(array &$data, Request $request): void { $data['is_current'] = $request->boolean('is_current'); }

    protected function afterSave(Model $record, bool $isNew): void
    {
        if ($record->is_current) AcademicYear::where('id', '!=', $record->id)->update(['is_current' => false]);   // only one current year
    }

    protected function extraColumns(): array
    {
        return ['Starts' => fn ($r) => $r->start_date->toDateString(), 'Ends' => fn ($r) => $r->end_date->toDateString(), 'Current' => fn ($r) => $r->is_current ? '✔ current' : ''];
    }
}
