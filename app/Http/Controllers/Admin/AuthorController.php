<?php

namespace App\Http\Controllers\Admin;

use App\Models\Author;
use Illuminate\Database\Eloquent\Model;

class AuthorController extends MasterController
{
    protected function modelClass(): string { return Author::class; }
    protected function title(): string { return 'Author'; }
    protected function routePrefix(): string { return 'admin.authors'; }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'bio', 'label' => 'Bio', 'type' => 'textarea'],
        ];
    }

    protected function rules(?Model $record): array
    {
        return [
            // letters, spaces, dots, apostrophes, hyphens only (Odoo _check_author_name)
            'name' => ['required', 'string', 'max:255', "regex:/^[\\w\\s.'-]+$/u"],
            'bio'  => ['nullable', 'string'],
        ];
    }

    protected function listQuery($query) { return $query->withCount('books'); }
    protected function extraColumns(): array { return ['Books' => fn ($r) => $r->books_count]; }
}
