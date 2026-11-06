<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\LmsException;
use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with(['author', 'category', 'department'])->withCount('issuedLoans');

        if ($s = trim((string) $request->query('q', ''))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('isbn', 'like', "%{$s}%")
                ->orWhereHas('author', fn ($a) => $a->where('name', 'like', "%{$s}%")));
        }
        if ($request->filled('state')) $query->where('state', $request->query('state'));
        if ($request->filled('department_id')) {
            $dept = Department::find($request->query('department_id'));
            if ($dept) $query->whereIn('department_id', $dept->descendantIds());
        }
        if ($request->filled('category_id')) $query->where('category_id', $request->query('category_id'));
        if ($request->query('type') === 'ebook') $query->where('is_ebook', true);
        if ($request->query('type') === 'physical') $query->where('is_ebook', false);
        // archived (inactive) records are hidden by default, like Odoo
        $query->where('active', ! $request->boolean('archived'));

        return view('admin.books.index', [
            'books'       => $query->latest('id')->paginate($this->perPage($request))->withQueryString(),
            'departments' => Department::options(),
            'categories'  => Category::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create()
    {
        return view('admin.books.form', $this->formData(new Book([
            'publication_year' => now()->year, 'version' => '1.0', 'copies_total' => 1,
        ])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->assertUniqueIdentity($data);

        $data = $this->handleUploads($request, $data);
        $data['version'] = '1.0';
        $book = Book::create($data);
        $book->logActivity("Book record created by {$request->user()->name}. Title: {$book->name}, version {$book->version}, state Draft.");

        return redirect()->route('admin.books.show', $book)->with('success', "Book created with ISBN {$book->isbn}.");
    }

    public function show(Request $request, Book $book)
    {
        $book->loadCount('issuedLoans')->load(['author', 'category', 'department', 'versionHistory.user', 'notifiedUsers',
            'activities.user', 'approvals.user', 'approvals.actor', 'loans' => fn ($q) => $q->with('member')->latest('id')->limit(10)]);

        // opening the record marks its notifications as read
        $request->user()->notifications()->whereNull('read_at')->get()
            ->filter(fn ($n) => (int) ($n->data['book_id'] ?? 0) === $book->id)->each->markAsRead();

        return view('admin.books.show', ['book' => $book, 'departments' => Department::options()]);
    }

    public function edit(Book $book)
    {
        return view('admin.books.form', $this->formData($book));
    }

    public function update(Request $request, Book $book)
    {
        $data = $this->validated($request, $book);
        $this->assertUniqueIdentity($data, $book);

        // e-book file may only be replaced while the book is a Draft
        if ($request->hasFile('ebook_file') && $book->state !== 'draft') {
            throw new LmsException('The e-book file can only be replaced while the book is in Draft.');
        }
        $data = $this->handleUploads($request, $data, $book);

        $book->updateGuarded($data, $request->user());

        return redirect()->route('admin.books.show', $book)->with('success', 'Book updated.');
    }

    public function destroy(Book $book)
    {
        if ($book->loans()->exists()) {
            throw new LmsException('This book has loan records and cannot be deleted. Mark it obsolete instead.');
        }
        foreach ([[$book->ebook_path, 'local'], [$book->cover_path, 'public']] as [$path, $disk]) {
            if ($path) Storage::disk($disk)->delete($path);
        }
        $book->delete();
        return redirect()->route('admin.books.index')->with('success', 'Book deleted.');
    }

    /* ----------------------------------------------------------- workflow buttons */

    public function submit(Request $request, Book $book)
    {
        $book->submitForReview($request->user());
        return back()->with('success', 'Submitted for review.');
    }

    public function approve(Request $request, Book $book)
    {
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:2000']]);
        $book->approve($request->user(), $data['comment'] ?? null);
        return back()->with('success', $book->fresh()->state === 'approved' ? 'Final approval given - the document is Approved.' : 'Your approval was recorded. The next approver has been notified.');
    }

    public function reject(Request $request, Book $book)
    {
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']]);
        $book->reject($request->user(), $data['comment']);
        return back()->with('success', 'Document rejected and sent back to Draft.');
    }

    public function release(Request $request, Book $book)
    {
        $book->release($request->user());
        return back()->with('success', 'Document released and notifications queued.');
    }

    public function obsolete(Request $request, Book $book)
    {
        $book->markObsolete($request->user());
        return back()->with('success', 'Document marked obsolete.');
    }

    /** Version wizard (lms.version.selector). */
    public function newVersion(Request $request, Book $book)
    {
        $data = $request->validate([
            'version_type'  => ['required', Rule::in(['minor', 'major'])],
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);

        $new = $book->createNewVersion($request->user(), $data['change_reason'], $data['version_type'] === 'major');
        return redirect()->route('admin.books.show', $new)->with('success', "Version {$new->version} created as Draft. The previous version is now obsolete.");
    }

    public function openEbook(Book $book)
    {
        return redirect()->route('ebook.view', $book);
    }

    /* ----------------------------------------------------------- helpers */

    private function formData(Book $book): array
    {
        return [
            'book'        => $book,
            'authors'     => Author::orderBy('name')->pluck('name', 'id'),
            'categories'  => Category::orderBy('name')->pluck('name', 'id'),
            'departments' => Department::options(),
        ];
    }

    private function validated(Request $request, ?Book $book = null): array
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255', 'regex:/^[\w\s\.,:;\'"!?()\-]+$/u'],
            'author_id'        => ['required', 'exists:authors,id'],
            'publication_year' => ['required', 'integer', 'between:1900,' . now()->year],
            'category_id'      => ['required', 'exists:categories,id'],
            'department_id'    => ['required', 'exists:departments,id'],
            'description'      => ['nullable', 'string'],
            'copies_total'     => ['required', 'integer', 'min:0'],
            'fine_per_day'     => ['nullable', 'numeric', 'min:0'],
            'change_reason'    => ['nullable', 'string', 'max:2000'],
            'ebook_file'       => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
            'cover_image'      => ['nullable', 'image', 'max:4096'],
        ], ['name.regex' => 'Book name contains invalid characters.']);

        $data['is_ebook'] = $request->boolean('is_ebook');
        $data['is_secure_ebook'] = $data['is_ebook'] && $request->boolean('is_secure_ebook');
        $data['fine_per_day'] = $data['fine_per_day'] ?? 0;
        if ($book === null) $data['active'] = true;
        unset($data['ebook_file'], $data['cover_image']);

        return $data;
    }

    /** UNIQUE(name, publication_year, version, department_id, category_id) */
    private function assertUniqueIdentity(array $data, ?Book $book = null): void
    {
        $exists = Book::where('name', $data['name'])->where('publication_year', $data['publication_year'])
            ->where('version', $book->version ?? '1.0')->where('department_id', $data['department_id'])
            ->where('category_id', $data['category_id'])->when($book, fn ($q) => $q->where('id', '!=', $book->id))->exists();

        if ($exists) {
            throw new LmsException('A book with the same Name, Year, Version, Department and Category already exists!');
        }
    }

    private function handleUploads(Request $request, array $data, ?Book $book = null): array
    {
        if ($request->hasFile('ebook_file')) {
            $file = $request->file('ebook_file');
            $data['ebook_path'] = $file->storeAs('ebooks', Str::uuid() . '.pdf', 'local');
            $data['ebook_filename'] = $file->getClientOriginalName();
            $data['total_pages'] = 1;                       // PDF.js reports the real value on first open
            if ($book?->ebook_path) Storage::disk('local')->delete($book->ebook_path);
        }
        if ($request->hasFile('cover_image')) {
            $data['cover_path'] = $request->file('cover_image')->store('covers', 'public');
            if ($book?->cover_path) Storage::disk('public')->delete($book->cover_path);
        }
        if ($data['is_ebook'] && ! ($data['ebook_path'] ?? $book?->ebook_path)) {
            throw new LmsException('Please upload the PDF file for an e-book.');
        }
        return $data;
    }
}
