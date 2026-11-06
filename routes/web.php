<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\EbookController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\Portal;
use Illuminate\Support\Facades\Route;

/* ------------------------------------------------------------------ public site */
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/feedback', [HomeController::class, 'feedback'])->name('feedback.store');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

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
    Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.change');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('password.update');
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
        Route::post('books/{book}/release', [Admin\BookController::class, 'release'])->name('books.release');
        Route::post('books/{book}/obsolete', [Admin\BookController::class, 'obsolete'])->name('books.obsolete');
        Route::post('books/{book}/version', [Admin\BookController::class, 'newVersion'])->name('books.version');
        Route::get('books/{book}/edit', [Admin\BookController::class, 'edit'])->name('books.edit');
        Route::put('books/{book}', [Admin\BookController::class, 'update'])->name('books.update');
    });
    Route::post('books/{book}/approve', [Admin\BookController::class, 'approve'])->middleware('perm:books,read')->name('books.approve');
    Route::post('books/{book}/reject', [Admin\BookController::class, 'reject'])->middleware('perm:books,read')->name('books.reject');
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

    // ------------------------------------------------------------------ School management (each screen = an access-rights resource)
    Route::prefix('school')->name('school.')->group(function () {
        // simple lists: set-up data, classes, exams, fee types, announcements
        foreach ([
            'academic-years' => [Admin\School\AcademicYearController::class, 'academics'],
            'terms'          => [Admin\School\TermController::class, 'academics'],
            'grade-levels'   => [Admin\School\GradeLevelController::class, 'academics'],
            'subjects'       => [Admin\School\SubjectController::class, 'academics'],
            'sections'       => [Admin\School\SectionController::class, 'academics'],
            'grade-scale'    => [Admin\School\GradeScaleController::class, 'exams'],
            'exams'          => [Admin\School\ExamController::class, 'exams'],
            'fee-types'      => [Admin\School\FeeTypeController::class, 'fees'],
            'announcements'  => [Admin\School\AnnouncementController::class, 'announcements'],
        ] as $uri => [$controller, $res]) {
            Route::get($uri, [$controller, 'index'])->middleware("perm:{$res},read")->name("{$uri}.index");
            Route::post($uri, [$controller, 'store'])->middleware("perm:{$res},create")->name("{$uri}.store");
            Route::put("{$uri}/{id}", [$controller, 'update'])->middleware("perm:{$res},update")->name("{$uri}.update");
            Route::delete("{$uri}/{id}", [$controller, 'destroy'])->middleware("perm:{$res},delete")->name("{$uri}.destroy");
        }
        Route::get('sections/{section}', [Admin\School\SectionController::class, 'show'])->middleware('perm:academics,read')->name('sections.show');
        Route::post('sections/{section}/subjects', [Admin\School\SectionController::class, 'assign'])->middleware('perm:academics,update')->name('sections.assign');
        Route::delete('sections/{section}/subjects/{assignment}', [Admin\School\SectionController::class, 'unassign'])->middleware('perm:academics,update')->name('sections.unassign');

        // students
        Route::get('students', [Admin\School\StudentController::class, 'index'])->middleware('perm:students,read')->name('students.index');
        Route::get('students/create', [Admin\School\StudentController::class, 'create'])->middleware('perm:students,create')->name('students.create');
        Route::post('students', [Admin\School\StudentController::class, 'store'])->middleware('perm:students,create')->name('students.store');
        Route::get('students/{student}', [Admin\School\StudentController::class, 'show'])->middleware('perm:students,read')->name('students.show');
        Route::get('students/{student}/edit', [Admin\School\StudentController::class, 'edit'])->middleware('perm:students,update')->name('students.edit');
        Route::put('students/{student}', [Admin\School\StudentController::class, 'update'])->middleware('perm:students,update')->name('students.update');
        Route::delete('students/{student}', [Admin\School\StudentController::class, 'destroy'])->middleware('perm:students,delete')->name('students.destroy');
        Route::get('promotion', [Admin\School\PromotionController::class, 'index'])->middleware('perm:students,update')->name('promotion.index');
        Route::post('promotion', [Admin\School\PromotionController::class, 'store'])->middleware('perm:students,update')->name('promotion.store');

        // teachers
        Route::get('teachers', [Admin\School\TeacherController::class, 'index'])->middleware('perm:teachers,read')->name('teachers.index');
        Route::get('teachers/create', [Admin\School\TeacherController::class, 'create'])->middleware('perm:teachers,create')->name('teachers.create');
        Route::post('teachers', [Admin\School\TeacherController::class, 'store'])->middleware('perm:teachers,create')->name('teachers.store');
        Route::get('teachers/{teacher}/edit', [Admin\School\TeacherController::class, 'edit'])->middleware('perm:teachers,read')->name('teachers.edit');
        Route::put('teachers/{teacher}', [Admin\School\TeacherController::class, 'update'])->middleware('perm:teachers,update')->name('teachers.update');
        Route::delete('teachers/{teacher}', [Admin\School\TeacherController::class, 'destroy'])->middleware('perm:teachers,delete')->name('teachers.destroy');

        // attendance / timetable / marks / report cards
        Route::get('attendance', [Admin\School\AttendanceController::class, 'index'])->middleware('perm:attendance,read')->name('attendance.index');
        Route::post('attendance', [Admin\School\AttendanceController::class, 'store'])->middleware('perm:attendance,update')->name('attendance.store');
        Route::get('timetable', [Admin\School\TimetableController::class, 'index'])->middleware('perm:timetable,read')->name('timetable.index');
        Route::post('timetable', [Admin\School\TimetableController::class, 'save'])->middleware('perm:timetable,update')->name('timetable.save');
        Route::get('marks', [Admin\School\MarksController::class, 'index'])->middleware('perm:exams,read')->name('marks.index');
        Route::post('marks', [Admin\School\MarksController::class, 'store'])->middleware('perm:exams,update')->name('marks.store');
        Route::get('report-card/{student}/{exam}', [Admin\School\ReportCardController::class, 'show'])->middleware('perm:exams,read')->name('report-card');

        // fees
        Route::get('invoices', [Admin\School\FeeInvoiceController::class, 'index'])->middleware('perm:fees,read')->name('invoices.index');
        Route::get('invoices/create', [Admin\School\FeeInvoiceController::class, 'create'])->middleware('perm:fees,create')->name('invoices.create');
        Route::post('invoices', [Admin\School\FeeInvoiceController::class, 'store'])->middleware('perm:fees,create')->name('invoices.store');
        Route::get('invoices/{invoice}', [Admin\School\FeeInvoiceController::class, 'show'])->middleware('perm:fees,read')->name('invoices.show');
        Route::post('invoices/{invoice}/payments', [Admin\School\FeeInvoiceController::class, 'pay'])->middleware('perm:fees,update')->name('invoices.pay');
        Route::post('invoices/{invoice}/void', [Admin\School\FeeInvoiceController::class, 'void'])->middleware('perm:fees,update')->name('invoices.void');
    });

    // Notifications (everyone with back-office access sees their own)
    Route::get('notifications', [Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [Admin\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('notifications/{id}/open', [Admin\NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/{id}/read', [Admin\NotificationController::class, 'read'])->name('notifications.read');

    // My approvals queue
    Route::get('approvals', [Admin\ApprovalQueueController::class, 'index'])->middleware('perm:books,read')->name('approvals.queue');

    // Approval flows: who approves which department's books, in which order (administrators only)
    Route::middleware('role:admin')->prefix('approval-flows')->name('flows.')->group(function () {
        Route::get('/', [Admin\ApprovalStepController::class, 'index'])->name('index');
        Route::post('/', [Admin\ApprovalStepController::class, 'store'])->name('store');
        Route::put('/{step}', [Admin\ApprovalStepController::class, 'update'])->name('update');
        Route::delete('/{step}', [Admin\ApprovalStepController::class, 'destroy'])->name('destroy');
        Route::post('/{step}/move', [Admin\ApprovalStepController::class, 'move'])->name('move');
    });

    // Access rights (administrators only - not delegable)
    Route::middleware('role:admin')->prefix('access-rights')->name('access.')->group(function () {
        Route::get('/', [Admin\AccessRightController::class, 'index'])->name('index');
        Route::post('/users', [Admin\AccessRightController::class, 'storeUser'])->name('users.store');
        Route::put('/{user}', [Admin\AccessRightController::class, 'update'])->name('update');
        Route::delete('/{user}', [Admin\AccessRightController::class, 'reset'])->name('reset');
        Route::post('/{user}/password', [Admin\AccessRightController::class, 'resetPassword'])->name('password');
    });
});

/* ------------------------------------------------------------------ teacher portal */
Route::middleware(['auth', 'portal:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [Portal\TeacherPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('timetable', [Portal\TeacherPortalController::class, 'timetable'])->name('timetable');
    Route::get('attendance', [Admin\School\AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance', [Admin\School\AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('marks', [Admin\School\MarksController::class, 'index'])->name('marks.index');
    Route::post('marks', [Admin\School\MarksController::class, 'store'])->name('marks.store');

    Route::get('notifications', [Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [Admin\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('notifications/{id}/open', [Admin\NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/{id}/read', [Admin\NotificationController::class, 'read'])->name('notifications.read');
});

/* ------------------------------------------------------------------ student portal */
Route::middleware(['auth', 'portal:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', [Portal\StudentPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('timetable', [Portal\StudentPortalController::class, 'timetable'])->name('timetable');
    Route::get('attendance', [Portal\StudentPortalController::class, 'attendance'])->name('attendance');
    Route::get('results', [Portal\StudentPortalController::class, 'results'])->name('results');
    Route::get('results/{exam}', [Portal\StudentPortalController::class, 'reportCard'])->name('report-card');
    Route::get('fees', [Portal\StudentPortalController::class, 'fees'])->name('fees');

    Route::get('library', [Portal\StudentPortalController::class, 'library'])->name('library');
    Route::post('library/{book}/request', [Portal\StudentPortalController::class, 'requestLoan'])->name('library.request');
    Route::delete('library/loans/{loan}', [Portal\StudentPortalController::class, 'cancelRequest'])->name('library.cancel');

    Route::get('notifications', [Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [Admin\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('notifications/{id}/open', [Admin\NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/{id}/read', [Admin\NotificationController::class, 'read'])->name('notifications.read');
});
