<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\LmsException;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/** Shared CRUD for the small master-data screens (authors, categories, departments, and the school set-up lists). */
abstract class MasterController extends Controller
{
    abstract protected function modelClass(): string;
    abstract protected function title(): string;
    abstract protected function routePrefix(): string;
    /** [['name' => 'x', 'label' => 'X', 'type' => 'text|textarea|select|date|number|checkbox', 'options' => [id => label]], ...] */
    abstract protected function fields(): array;
    abstract protected function rules(?Model $record): array;

    /** Access-rights resource that guards this screen (shown / hidden buttons). */
    protected function permResource(): string { return Str::after($this->routePrefix(), 'admin.'); }
    protected function searchColumn(): string { return 'name'; }
    protected function orderColumn(): string { return 'name'; }
    protected function orderDirection(): string { return 'asc'; }
    protected function parentOptions(): array { return []; }
    protected function listQuery($query) { return $query; }
    protected function extraColumns(): array { return []; }   // [label => callable($row)]

    /** Runs right after validation (use it to read checkboxes etc.). */
    protected function prepare(array &$data, Request $request): void {}
    protected function beforeSave(array &$data, ?Model $record): void {}
    protected function afterSave(Model $record, bool $isNew): void {}

    public function index(Request $request)
    {
        $class = $this->modelClass();
        $query = $this->listQuery($class::query());
        if ($s = trim((string) $request->query('q', ''))) $query->where($this->searchColumn(), 'like', "%{$s}%");

        return view('admin.masters.index', [
            'title'   => $this->title(),
            'prefix'  => $this->routePrefix(),
            'perm'    => $this->permResource(),
            'hasShow' => Route::has($this->routePrefix() . '.show'),
            'fields'  => $this->fields(),
            'rows'    => $query->orderBy($this->orderColumn(), $this->orderDirection())->paginate($this->perPage($request, 25))->withQueryString(),
            'parents' => $this->parentOptions(),
            'extra'   => $this->extraColumns(),
        ]);
    }

    public function store(Request $request)
    {
        $class = $this->modelClass();
        $data = $request->validate($this->rules(null));
        $this->prepare($data, $request);
        $this->beforeSave($data, null);
        $record = $class::create($data);
        $this->afterSave($record, true);
        return back()->with('success', $this->title() . ' created.');
    }

    public function update(Request $request, int $id)
    {
        $class = $this->modelClass();
        $record = $class::findOrFail($id);
        $data = $request->validate($this->rules($record));
        $this->prepare($data, $request);
        $this->beforeSave($data, $record);
        $record->update($data);
        $this->afterSave($record, false);
        return back()->with('success', $this->title() . ' updated.');
    }

    public function destroy(int $id)
    {
        $class = $this->modelClass();
        try {
            $class::findOrFail($id)->delete();
        } catch (QueryException $e) {
            throw new LmsException('This record is still in use and cannot be deleted.');
        }
        return back()->with('success', 'Deleted.');
    }
}
