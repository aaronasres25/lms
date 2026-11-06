<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Admin\MasterController;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;

class SubjectController extends MasterController
{
    protected function modelClass(): string { return Subject::class; }
    protected function title(): string { return 'Subject'; }
    protected function routePrefix(): string { return 'admin.school.subjects'; }
    protected function permResource(): string { return 'academics'; }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'code', 'label' => 'Code (MATH)', 'type' => 'text'],
        ];
    }

    protected function rules(?Model $record): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'code' => ['nullable', 'string', 'max:20']];
    }

    protected function extraColumns(): array { return ['Code' => fn ($r) => $r->code]; }
}
