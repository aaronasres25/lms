<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Feedback;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $base = Book::released()->where('is_ebook', true);
        return view('home', [
            'ebookCount' => (clone $base)->count(),
            'latest'     => (clone $base)->with('author')->latest()->take(4)->get(),
        ]);
    }

    /** Public feedback form (/lms/submit_feedback). */
    public function feedback(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'],
            'email'      => ['nullable', 'email', 'max:255'],
            'comment'    => ['required', 'string', 'max:5000'],
        ]);

        Feedback::create([
            'name'    => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: 'Anonymous Reader',
            'email'   => $data['email'] ?? null,
            'comment' => $data['comment'],
        ]);

        return back()->with('success', 'Thank you! Your feedback has been received.');
    }
}
