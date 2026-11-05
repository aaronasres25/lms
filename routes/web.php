<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\EbookController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/* ------------------------------------------------------------------ public site */
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/feedback', [HomeController::class, 'feedback'])->name('feedback.store');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');

Route::get('/books/{book}/open', [EbookController::class, 'open'])->name('ebook.open');
Route::get('/ebooks/{book}', [EbookController::class, 'view'])->name('ebook.view');
Route::get('/ebooks/{book}/file', [EbookController::class, 'file'])->name('ebook.file');

/* ------------------------------------------------------------------ auth */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/ebooks/{book}/download', [EbookController::class, 'download'])->name('ebook.download');
    Route::get('/ebooks/{book}/progress', [EbookController::class, 'getProgress'])->name('ebook.progress.get');
    Route::post('/ebooks/{book}/progress', [EbookController::class, 'saveProgress'])->name('ebook.progress.save');
});

/* ------------------------------------------------------------------ back-office
 * Every action is protected by  perm:<resource>,<read|create|update|delete>.
 * The effective right = the user's custom override (Access Rights screen) or the role default
 * defined in config/lms.php. Admins always pass.
 * Book workflow buttons (submit/approve/release/obsolete/new version) additionally enforce the
 * QA / Manager role inside the Book model.
 */
Route::middleware(['auth', 'role:user'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard + JSON feeds
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/overview', [Admin\DashboardController::class, 'overview'])->name('dashboard.overview');
    Route::get('/dashboard/kpis', [Admin\DashboardController::class, 'kpis'])->name('dashboard.kpis');
    Route::get('/dashboard/charts', [Admin\DashboardController::class, 'charts'])->name('dashboard.charts');
    Route::get('/dashboard/login-report', [Admin\DashboardController::class, 'loginReport'])->name('dashboard.login-report');
    Route::get('/dashboard/recent-reads', [Admin\DashboardController::class, 'recentReads'])->name('dashboard.recent-reads');

    // Books
    Route::get('books', [Admin\BookController::class, 'index'])->middleware('perm:books,read')->name('books.index');
    Route::get('books/create', [Admin\BookController::class, 'create'])->middleware('perm:books,create')->name('books.create');
    Route::post('books', [Admin\BookController::class, 'store'])->middleware('perm:books,create')->name('books.store');
    Route::middleware('perm:books,update')->group(function () {
        Route::post('books/{book}/submit', [Admin\BookController::class, 'submit'])->name('books.submit');
        Route::post('books/{book}/approve', [Admin\BookController::class, 'approve'])->name('books.approve');
        Route::post('books/{book}/release', [Admin\BookController::class, 'release'])->name('books.release');
        Route::post('books/{book}/obsolete', [Admin\BookController::class, 'obsolete'])->name('books.obsolete');
        Route::post('books/{book}/version', [Admin\BookController::class, 'newVersion'])->name('books.version');
        Route::get('books/{book}/edit', [Admin\BookController::class, 'edit'])->name('books.edit');
        Route::put('books/{book}', [Admin\BookController::class, 'update'])->name('books.update');
    });
    Route::get('books/{book}', [Admin\BookController::class, 'show'])->middleware('perm:books,read')->name('books.show');
    Route::delete('books/{book}', [Admin\BookController::class, 'destroy'])->middleware('perm:books,delete')->name('books.destroy');

    // Loans
    Route::get('loans', [Admin\LoanController::class, 'index'])->middleware('perm:loans,read')->name('loans.index');
    Route::get('loans/create', [Admin\LoanController::class, 'create'])->middleware('perm:loans,create')->name('loans.create');
    Route::post('loans', [Admin\LoanController::class, 'store'])->middleware('perm:loans,create')->name('loans.store');
    Route::middleware('perm:loans,update')->group(function () {
        Route::post('loans/{loan}/issue', [Admin\LoanController::class, 'issue'])->name('loans.issue');
        Route::post('loans/{loan}/return', [Admin\LoanController::class, 'return'])->name('loans.return');
        Route::post('loans/{loan}/lost', [Admin\LoanController::class, 'lost'])->name('loans.lost');
    });
    Route::get('loans/{loan}', [Admin\LoanController::class, 'show'])->middleware('perm:loans,read')->name('loans.show');
    Route::delete('loans/{loan}', [Admin\LoanController::class, 'destroy'])->middleware('perm:loans,delete')->name('loans.destroy');

    // Members
    Route::get('members', [Admin\MemberController::class, 'index'])->middleware('perm:members,read')->name('members.index');
    Route::get('members/create', [Admin\MemberController::class, 'create'])->middleware('perm:members,create')->name('members.create');
    Route::post('members', [Admin\MemberController::class, 'store'])->middleware('perm:members,create')->name('members.store');
    Route::get('members/{member}/edit', [Admin\MemberController::class, 'edit'])->middleware('perm:members,update')->name('members.edit');
    Route::put('members/{member}', [Admin\MemberController::class, 'update'])->middleware('perm:members,update')->name('members.update');
    Route::get('members/{member}', [Admin\MemberController::class, 'show'])->middleware('perm:members,read')->name('members.show');
    Route::delete('members/{member}', [Admin\MemberController::class, 'destroy'])->middleware('perm:members,delete')->name('members.destroy');

    // Master data
    foreach (['authors' => Admin\AuthorController::class, 'categories' => Admin\CategoryController::class, 'departments' => Admin\DepartmentController::class] as $uri => $controller) {
        Route::get($uri, [$controller, 'index'])->middleware("perm:{$uri},read")->name("{$uri}.index");
        Route::post($uri, [$controller, 'store'])->middleware("perm:{$uri},create")->name("{$uri}.store");
        Route::put("{$uri}/{id}", [$controller, 'update'])->middleware("perm:{$uri},update")->name("{$uri}.update");
        Route::delete("{$uri}/{id}", [$controller, 'destroy'])->middleware("perm:{$uri},delete")->name("{$uri}.destroy");
    }

    // Reports
    Route::middleware('perm:reports,read')->group(function () {
        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');
        Route::get('reports/summary', [Admin\ReportController::class, 'summary'])->name('reports.summary');
    });

    // Feedback inbox
    Route::get('feedback', [Admin\FeedbackController::class, 'index'])->middleware('perm:feedback,read')->name('feedback.index');
    Route::delete('feedback/{feedback}', [Admin\FeedbackController::class, 'destroy'])->middleware('perm:feedback,delete')->name('feedback.destroy');

    // Access rights (administrators only - not delegable)
    Route::middleware('role:admin')->prefix('access-rights')->name('access.')->group(function () {
        Route::get('/', [Admin\AccessRightController::class, 'index'])->name('index');
        Route::post('/users', [Admin\AccessRightController::class, 'storeUser'])->name('users.store');
        Route::put('/{user}', [Admin\AccessRightController::class, 'update'])->name('update');
        Route::delete('/{user}', [Admin\AccessRightController::class, 'reset'])->name('reset');
    });
});
