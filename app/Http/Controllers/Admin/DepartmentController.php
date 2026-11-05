<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\LmsException;
use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class DepartmentController extends MasterController
{
    protected function modelClass(): string { return Department::class; }
    protected function title(): string { return 'Department'; }
    protected function routePrefix(): string { return 'admin.departments'; }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'parent_id', 'label' => 'Parent Department', 'type' => 'select'],
        ];
    }

    protected function rules(?Model $record): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:departments,id', Rule::notIn($record ? [$record->id] : [])],
        ];
    }

    protected function beforeSave(array &$data, ?Model $record): void
    {
        $data['parent_id'] = $data['parent_id'] ?? null;
        // unique(name, parent_id) - the DB index can't see NULL parents, so check it here
        $dup = Department::where('name', $data['name'])->where('parent_id', $data['parent_id'])
            ->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists();
        if ($dup) throw new LmsException('The department name must be unique under the same parent!');

        // prevent cycles: a department cannot move under one of its own descendants
        if ($record && $data['parent_id'] && in_array((int) $data['parent_id'], $record->descendantIds(), true)) {
            throw new LmsException('A department cannot be moved under its own sub-department.');
        }
    }

    protected function parentOptions(): array { return Department::options(); }
    protected function listQuery($query) { return $query->with('parent'); }
    protected function extraColumns(): array
    {
        return ['Full path' => fn ($r) => $r->complete_name, 'Books (incl. sub-departments)' => fn ($r) => $r->book_count];
    }
}
