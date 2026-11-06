<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Admin\MasterController;
use App\Models\GradeLevel;
use Illuminate\Database\Eloquent\Model;

class GradeLevelController extends MasterController
{
    protected function modelClass(): string { return GradeLevel::class; }
    protected function title(): string { return 'Grade level'; }
    protected function routePrefix(): string { return 'admin.school.grade-levels'; }
    protected function permResource(): string { return 'academics'; }
    protected function orderColumn(): string { return 'level_order'; }
    protected function listQuery($query) { return $query->withCount('sections'); }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name (Grade 5)', 'type' => 'text', 'required' => true],
            ['name' => 'level_order', 'label' => 'Order (1, 2, 3…)', 'type' => 'number', 'required' => true],
        ];
    }

    protected function rules(?Model $record): array
    {
        return ['name' => ['required', 'string', 'max:50'], 'level_order' => ['required', 'integer', 'min:0', 'max:100']];
    }

    protected function extraColumns(): array { return ['Order' => fn ($r) => $r->level_order, 'Sections' => fn ($r) => $r->sections_count]; }
}
