<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Department;
use App\Models\Viewer;
use Illuminate\Http\Request;

/** Public "Netflix style" e-book catalog (/catalog). */
class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $filterBy = $request->query('filter_by') === 'category' ? 'category' : 'department';
        $search = trim((string) $request->query('search', ''));

        $query = Book::released()->where('is_ebook', true)->with(['author', 'category']);

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('author', fn ($a) => $a->where('name', 'like', "%{$search}%")));
        }

        $selectedDept = null;
        if ($request->filled('dept_id') && ctype_digit((string) $request->query('dept_id'))) {
            $selectedDept = Department::find((int) $request->query('dept_id'));
            if ($selectedDept) $query->whereIn('department_id', $selectedDept->descendantIds());
        }

        $books = $query->orderBy('name')->get();

        // group rows (one "shelf" per department or category)
        $deptNames = Department::options();
        $groups = $filterBy === 'department'
            ? $books->groupBy(fn ($b) => $deptNames[$b->department_id] ?? 'Other')
            : $books->groupBy(fn ($b) => $b->category->name ?? 'Uncategorised');
        $groups = $groups->sortKeys();

        // "Continue reading" row for the signed-in user
        $history = collect();
        if ($user = $request->user()) {
            $history = Viewer::with('book.author')->where('user_id', $user->id)
                ->whereHas('book', fn ($b) => $b->released())->latest('last_read_at')->take(8)->get();
        }

        // footer discovery blocks (fallback to the whole catalogue when a filter returns nothing)
        $pool = $books->isNotEmpty() ? $books : Book::released()->where('is_ebook', true)->with('author')->get();

        return view('catalog', [
            'groups'        => $groups,
            'history'       => $history,
            'filterBy'      => $filterBy,
            'search'        => $search,
            'selectedDept'  => $selectedDept,
            'departments'   => $deptNames,
            'topDepartments'=> Department::whereNull('parent_id')->where('active', true)->orderBy('name')->get(['id', 'name']),
            'recentBooks'   => $pool->sortByDesc('created_at')->take(3),
            'popularBooks'  => $pool->sortByDesc('ebook_read_count')->take(3),
            'randomBooks'   => $pool->shuffle()->take(3),
        ]);
    }
}
