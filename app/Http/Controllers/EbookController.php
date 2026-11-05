<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Viewer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;

/** PDF.js reader + file streaming + reading progress (port of lms_pdfviewer.py / main.py). */
class EbookController extends Controller
{
    /** /books/{book}/open  (was /lms/book/<id>) - entry point used by catalog cards and e-mails. */
    public function open(Book $book)
    {
        if ($book->state !== 'released' && ! auth()->user()?->isStaff()) {
            return redirect()->route('home')->with('error', 'This book is not available.');
        }
        if ($book->is_ebook && $book->ebook_path) {
            return redirect()->route('ebook.view', $book);
        }
        // physical book: staff go to the back-office record
        return auth()->user()?->isStaff()
            ? redirect()->route('admin.books.show', $book)
            : redirect()->route('catalog')->with('error', 'This title is a physical book - visit the library to borrow it.');
    }

    public function view(Request $request, Book $book)
    {
        $user = $request->user();

        // secure e-books need an account (checked BEFORE counting a read)
        if ($book->is_secure_ebook && ! $user) {
            $request->session()->put('url.intended', route('ebook.view', $book));
            return redirect()->route('login')->with('error', 'Please sign in to read this secure document.');
        }
        if ($error = $book->readAccessError($user)) {
            abort(404, $error);
        }

        $book->registerRead($user);

        // welcome banner (cookie holds the previous visit timestamp)
        $welcome = '';
        $last = $request->cookie('lms_last_visit');
        if ($last && is_numeric($last)) {
            $days = (int) floor((time() - (float) $last) / 86400);
            $welcome = $days >= 1
                ? "Welcome back! It's been {$days} days - welcome to the " . config('lms.company_name') . ' Digital Library!'
                : 'Welcome back to the ' . config('lms.company_name') . ' Digital Library!';
        } elseif (! $user) {
            $welcome = 'Welcome to the ' . config('lms.company_name') . ' Digital Library! Sign in to save your reading progress.';
        }
        Cookie::queue('lms_last_visit', (string) time(), 60 * 24 * 30);

        return view('viewer', [
            'book'        => $book,
            'currentPage' => $user ? Viewer::progressFor($user->id, $book->id) : 1,
            'welcome'     => $welcome,
        ]);
    }

    /** Streams the PDF inline (never exposes the storage path). */
    public function file(Request $request, Book $book)
    {
        $user = $request->user();
        if ($book->is_secure_ebook && ! $user) abort(403);
        if ($book->readAccessError($user)) abort(404);

        return Storage::disk('local')->response($book->ebook_path, $book->ebook_filename ?: "{$book->name}.pdf", [
            'Content-Type'           => 'application/pdf',
            'Content-Disposition'    => 'inline',
            'Cache-Control'          => 'no-cache, no-store, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(Request $request, Book $book)
    {
        if ($book->readAccessError($request->user())) abort(404);
        return Storage::disk('local')->download($book->ebook_path, $book->ebook_filename ?: "{$book->name}.pdf");
    }

    public function getProgress(Request $request, Book $book)
    {
        return response()->json(['success' => true, 'current_page' => Viewer::progressFor($request->user()->id, $book->id)]);
    }

    public function saveProgress(Request $request, Book $book)
    {
        $data = $request->validate(['page_number' => ['required', 'integer', 'min:1'], 'total_pages' => ['nullable', 'integer', 'min:1']]);

        Viewer::saveProgress($request->user()->id, $book->id, $data['page_number']);

        // PDF.js reports the real page count the first time (replaces the file-size estimate)
        if (! empty($data['total_pages']) && $book->total_pages <= 1) {
            $book->forceFill(['total_pages' => $data['total_pages']])->saveQuietly();
        }
        return response()->json(['success' => true, 'message' => 'Progress updated successfully']);
    }
}
