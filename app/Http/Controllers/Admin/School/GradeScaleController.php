<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Admin\MasterController;
use App\Models\GradeScale;
use Illuminate\Database\Eloquent\Model;

class GradeScaleController extends MasterController
{
    protected function modelClass(): string { return GradeScale::class; }
    protected function title(): string { return 'Grade'; }
    protected function routePrefix(): string { return 'admin.school.grade-scale'; }
    protected function permResource(): string { return 'exams'; }
    protected function orderColumn(): string { return 'min_percent'; }
    protected function orderDirection(): string { return 'desc'; }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Letter (A)', 'type' => 'text', 'required' => true],
            ['name' => 'min_percent', 'label' => 'From % (inclusive)', 'type' => 'number', 'required' => true],
            ['name' => 'remark', 'label' => 'Remark (Excellent)', 'type' => 'text'],
        ];
    }

    protected function rules(?Model $record): array
    {
        return ['name' => ['required', 'string', 'max:5'], 'min_percent' => ['required', 'numeric', 'between:0,100'], 'remark' => ['nullable', 'string', 'max:50']];
    }

    protected function extraColumns(): array { return ['From %' => fn ($r) => rtrim(rtrim(number_format($r->min_percent, 2), '0'), '.'), 'Remark' => fn ($r) => $r->remark]; }
}
