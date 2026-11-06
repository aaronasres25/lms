<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Department;

/** Public About and Contact pages (separate from Home). */
class PageController extends Controller
{
    public function about()
    {
        return view('pages.about', [
            'stats' => [
                'E-books' => Book::released()->where('is_ebook', true)->count(),
                'Departments' => Department::where('active', true)->count(),
                'Categories' => Category::count(),
                'Authors' => Author::count(),
            ],
        ]);
    }

    public function contact()
    {
        return view('pages.contact', ['contact' => config('lms.contact')]);
    }
}
