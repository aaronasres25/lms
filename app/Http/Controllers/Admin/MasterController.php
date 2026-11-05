<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\LmsException;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

/** Shared CRUD for the small master-data screens (authors, categories, departments). */
abstract class MasterController extends Controller
{
    abstract protected function modelClass(): string;
    abstract protected function title(): string;
    abstract protected function routePrefix(): string;
    /** [['name' => 'name', 'label' => 'Name', 'type' => 'text|textarea|select'], ...] */
    abstract protected function fields(): array;
    abstract protected function rules(?Model $record): array;

    protected function parentOptions(): array { return []; }
    protected function listQuery($query) { return $query; }
    protected function extraColumns(): array { return []; }   // [label => callable($row)]

    public function index(Request $request)
    {
        $class = $this->modelClass();
        $query = $this->listQuery($class::query());
        if ($s = trim((string) $request->query('q', ''))) $query->where('name', 'like', "%{$s}%");

        return view('admin.masters.index', [
            'title'   => $this->title(),
            'prefix'  => $this->routePrefix(),
            'fields'  => $this->fields(),
            'rows'    => $query->orderBy('name')->paginate($this->perPage($request, 25))->withQueryString(),
            'parents' => $this->parentOptions(),
            'extra'   => $this->extraColumns(),
        ]);
    }

    public function store(Request $request)
    {
        $class = $this->modelClass();
        $data = $request->validate($this->rules(null));
        $this->beforeSave($data, null);
        $class::create($data);
        return back()->with('success', $this->title() . ' created.');
    }

    public function update(Request $request, int $id)
    {
        $class = $this->modelClass();
        $record = $class::findOrFail($id);
        $data = $request->validate($this->rules($record));
        $this->beforeSave($data, $record);
        $record->update($data);
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

    protected function beforeSave(array &$data, ?Model $record): void {}
}
