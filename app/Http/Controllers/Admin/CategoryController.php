<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CategoryController extends MasterController
{
    protected function modelClass(): string { return Category::class; }
    protected function title(): string { return 'Category'; }
    protected function routePrefix(): string { return 'admin.categories'; }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'parent_id', 'label' => 'Parent Category', 'type' => 'select'],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
        ];
    }

    protected function rules(?Model $record): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'parent_id'   => ['nullable', 'exists:categories,id', Rule::notIn($record ? [$record->id] : [])],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function parentOptions(): array { return Category::orderBy('name')->pluck('name', 'id')->all(); }
    protected function listQuery($query) { return $query->with('parent')->withCount('books'); }
    protected function extraColumns(): array
    {
        return ['Parent' => fn ($r) => $r->parent?->name ?? '—', 'Books' => fn ($r) => $r->books_count];
    }
}
