<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ApprovalStep;
use App\Models\Attendance;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\FeeInvoice;
use App\Models\FeeType;
use App\Models\GradeLevel;
use App\Models\Loan;
use App\Models\Member;
use App\Models\ReadHistory;
use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Models\Viewer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Loads demo data from JSON files (database/demo/*.json) for the library AND the school.
 * Safe to run repeatedly: every record is looked up by a natural key first (e-mail, admission no, ISBN-less book identity ...).
 * "Random" numbers are derived from hashes, so the same files always produce the same data.
 */
class DemoImporter
{
    /** section => file. Order matters (dependencies first). */
    public const FILES = [
        'users'         => 'users.json',
        'flows'         => 'approval_flows.json',
        'books'         => 'books.json',
        'members'       => 'members.json',
        'subjects'      => 'subjects.json',
        'teachers'      => 'teachers.json',
        'sections'      => 'sections.json',
        'students'      => 'students.json',
        'timetable'     => 'timetable.json',
        'exams'         => 'exams.json',
        'marks'         => 'marks.json',
        'attendance'    => 'attendance.json',
        'fees'          => 'fees.json',
        'announcements' => 'announcements.json',
    ];

    private const FIRST_M = ['Abel', 'Dawit', 'Samuel', 'Yonas', 'Henok', 'Kaleb', 'Natnael', 'Biruk', 'Mikias', 'Tewodros', 'Bereket', 'Eyob', 'Robel', 'Amanuel', 'Fitsum', 'Nahom', 'Surafel', 'Yohannes'];
    private const FIRST_F = ['Selam', 'Hana', 'Meron', 'Liya', 'Rahel', 'Saron', 'Bethlehem', 'Tsion', 'Eden', 'Marta', 'Kidist', 'Helen', 'Sara', 'Blen', 'Ruth', 'Mekdes', 'Feven', 'Lidya'];
    private const LAST = ['Tadesse', 'Bekele', 'Alemu', 'Girma', 'Haile', 'Tesfaye', 'Kebede', 'Mengistu', 'Assefa', 'Desta', 'Worku', 'Negash', 'Abebe', 'Getachew', 'Lemma', 'Wolde', 'Teshome', 'Mulugeta'];

    private array $stats = [];
    private ?AcademicYear $year = null;
    private array $sectionCache = [];

    public function __construct(private string $path) {}

    /** @return array<string, array<string,int>> counts per section */
    public function run(array $only = [], ?callable $progress = null): array
    {
        $this->year = AcademicYear::current();
        if (! $this->year) throw new RuntimeException('No academic year found. Run "php artisan migrate --seed" first.');

        foreach (array_keys(self::FILES) as $section) {
            if ($only && ! in_array($section, $only, true)) continue;
            if ($progress) $progress($section);
            $this->{'import' . Str::studly($section)}();
        }
        return $this->stats;
    }

    /** Delete library + school data (users and set-up lists are kept). */
    public static function wipe(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['fee_payments', 'fee_invoice_items', 'fee_invoices', 'announcements', 'exam_marks', 'exams', 'attendances', 'timetable_slots',
                  'enrollments', 'students', 'section_subjects', 'sections', 'teachers', 'loans', 'members', 'book_approvals', 'approval_steps',
                  'read_histories', 'viewers', 'book_version_histories', 'book_notifications', 'books', 'authors'] as $table) {
            DB::table($table)->delete();
        }
        DB::table('activity_logs')->whereIn('loggable_type', [Book::class, Loan::class, Member::class])->delete();
        Schema::enableForeignKeyConstraints();

        foreach (Storage::disk('local')->files('ebooks') as $file) {
            if (str_contains(basename($file), 'demo-')) Storage::disk('local')->delete($file);
        }
    }

    /* ------------------------------------------------------------------ helpers */

    private function bump(string $section, string $key = 'created', int $n = 1): void
    {
        $this->stats[$section][$key] = ($this->stats[$section][$key] ?? 0) + $n;
    }

    private function load(string $section): ?array
    {
        $file = rtrim($this->path, '/\\') . DIRECTORY_SEPARATOR . self::FILES[$section];
        if (! is_file($file)) {
            $this->bump($section, 'file missing');
            return null;
        }
        try {
            $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException(basename($file) . ': invalid JSON - ' . $e->getMessage());
        }
        return is_array($data) ? $data : null;
    }

    /** stable pseudo-random number in [0,1) */
    private function r(string $key): float
    {
        return (crc32($key) % 100000) / 100000;
    }

    private function ri(string $key, int $min, int $max): int
    {
        return $min + (int) floor($this->r($key) * ($max - $min + 1));
    }

    private function user(?string $email): ?User
    {
        return $email ? User::where('email', $email)->first() : null;
    }

    private function teacher(?string $employeeNo): ?Teacher
    {
        return $employeeNo ? Teacher::where('employee_no', $employeeNo)->first() : null;
    }

    /** "Grade 5-A" -> the Section of the current academic year */
    private function section(string $label): ?Section
    {
        if (array_key_exists($label, $this->sectionCache)) return $this->sectionCache[$label];
        $section = null;
        if (preg_match('/^(.*)-\s*([^-]+)$/', trim($label), $m)) {
            $grade = GradeLevel::where('name', trim($m[1]))->first();
            if ($grade) {
                $section = Section::where('grade_level_id', $grade->id)->where('academic_year_id', $this->year->id)->where('name', trim($m[2]))->first();
            }
        }
        return $this->sectionCache[$label] = $section;
    }

    /* ------------------------------------------------------------------ users */

    private function importUsers(): void
    {
        foreach ($this->load('users') ?? [] as $u) {
            $role = $u['role'] ?? 'reader';
            if (! array_key_exists($role, User::ROLES)) throw new RuntimeException("users.json: unknown role '{$role}'.");

            if (User::where('email', $u['email'])->exists()) { $this->bump('users', 'skipped'); continue; }
            User::create(['name' => $u['name'], 'email' => $u['email'], 'password' => $u['password'] ?? 'password', 'role' => $role,
                'active' => true, 'must_change_password' => false]);
            $this->bump('users');
        }
    }

    /* ------------------------------------------------------------------ approval flows */

    private function importFlows(): void
    {
        foreach ($this->load('flows') ?? [] as $f) {
            $dept = ! empty($f['department']) ? Department::findOrCreatePath($f['department']) : null;
            foreach (array_values($f['steps'] ?? []) as $i => $step) {
                $row = ApprovalStep::firstOrCreate(['department_id' => $dept?->id, 'sequence' => $i + 1],
                    ['title' => $step['title'], 'role' => $step['role'] ?? null, 'user_id' => $this->user($step['user'] ?? null)?->id]);
                $this->bump('flows', $row->wasRecentlyCreated ? 'created' : 'skipped');
            }
        }
    }

    /* ------------------------------------------------------------------ books */

    private function importBooks(): void
    {
        $d = $this->load('books');
        if (! $d) return;

        foreach ($d['authors'] ?? [] as $a) Author::firstOrCreate(['name' => $a['name']], ['bio' => $a['bio'] ?? null]);
        foreach ($d['categories'] ?? [] as $c) Category::firstOrCreate(['name' => is_array($c) ? $c['name'] : $c]);

        $submitter = User::where('role', 'qa')->first() ?? User::where('role', 'admin')->first();

        foreach ($d['books'] ?? [] as $b) {
            $author = Author::firstOrCreate(['name' => $b['author']]);
            $category = Category::firstOrCreate(['name' => $b['category']]);
            $dept = Department::findOrCreatePath($b['department'] ?? 'Documentation');
            $year = min((int) ($b['publication_year'] ?? now()->year - 1), now()->year);
            $version = $b['version'] ?? '1.0';

            $exists = Book::where('name', $b['name'])->where('publication_year', $year)->where('version', $version)
                ->where('department_id', $dept->id)->where('category_id', $category->id)->exists();
            if ($exists) { $this->bump('books', 'skipped'); continue; }

            $isEbook = (bool) ($b['is_ebook'] ?? false);
            $state = $b['state'] ?? 'released';
            $approved = in_array($state, ['approved', 'released', 'obsolete'], true);

            $book = Book::create([
                'name' => $b['name'], 'author_id' => $author->id, 'category_id' => $category->id, 'department_id' => $dept->id,
                'publication_year' => $year, 'description' => $b['description'] ?? null,
                'copies_total' => (int) ($b['copies'] ?? ($isEbook ? 0 : 1)),          // e-book only titles have no physical copies
                'fine_per_day' => $b['fine_per_day'] ?? 0, 'version' => $version,
                'state' => $state === 'review' ? 'draft' : $state, 'active' => $state !== 'obsolete',
                'is_ebook' => $isEbook, 'is_secure_ebook' => (bool) ($b['secure'] ?? false),
                'approval_date' => $approved ? today()->subDays(30) : null, 'expiry_date' => $approved ? today()->addYears(2) : null,
            ]);

            if ($isEbook) $this->attachPdf($book, (int) ($b['pages'] ?? 8), (int) ($b['reads'] ?? $this->ri('reads' . $b['name'], 5, 90)));

            // "review": run the real submit so the approval chain + notifications exist
            if ($state === 'review' && $submitter) {
                try { $book->submitForReview($submitter); } catch (\Throwable $e) { $this->bump('books', 'review failed'); }
            }
            $this->bump('books');
        }
    }

    private function attachPdf(Book $book, int $pages, int $reads): void
    {
        $path = 'ebooks/demo-' . Str::slug($book->name) . '-' . $book->id . '.pdf';
        Storage::disk('local')->put($path, DemoPdf::make($book->name, $pages));
        $book->forceFill(['ebook_path' => $path, 'ebook_filename' => $book->name . '.pdf', 'total_pages' => $pages,
            'ebook_read_count' => $reads, 'last_opened' => now()->subDays($this->ri('lo' . $book->id, 0, 10))])->save();

        // spread the reads over 60 days so the dashboard "reading activity" charts have something to show
        $rows = [];
        for ($i = 0; $i < min($reads, 60); $i++) {
            $when = now()->subDays($this->ri("rd{$book->id}-{$i}", 0, 59))->subMinutes($this->ri("rm{$book->id}-{$i}", 0, 1200))->toDateTimeString();
            $rows[] = ['book_id' => $book->id, 'user_id' => null, 'category_id' => $book->category_id, 'department_id' => $book->department_id, 'created_at' => $when, 'updated_at' => $when];
        }
        if ($rows) ReadHistory::insert($rows);
    }

    /* ------------------------------------------------------------------ library members + loans */

    private function importMembers(): void
    {
        $d = $this->load('members');
        if (! $d) return;

        foreach ($d['members'] ?? [] as $m) {
            $user = $this->user($m['email'] ?? null);
            $member = Member::firstOrCreate(['name' => $m['name'], 'user_id' => $user?->id], [
                'membership_start' => today()->subDays((int) ($m['started_days_ago'] ?? 60)),
                'membership_end' => today()->addDays((int) ($m['valid_days'] ?? 300)),
                'max_books' => (int) ($m['max_books'] ?? 5),
            ]);
            $this->bump('members', $member->wasRecentlyCreated ? 'created' : 'skipped');
        }

        foreach ($d['loans'] ?? [] as $l) {
            $book = Book::where('name', $l['book'])->first();
            $member = Member::where('name', $l['member'])->first();
            if (! $book || ! $member) { $this->bump('loans', 'skipped (book/member not found)'); continue; }

            $loanDate = today()->subDays((int) ($l['loaned_days_ago'] ?? 5));
            if (Loan::where('book_id', $book->id)->where('member_id', $member->id)->whereDate('loan_date', $loanDate->toDateString())->exists()) {
                $this->bump('loans', 'skipped'); continue;
            }
            $state = $l['state'] ?? 'issued';
            $loan = Loan::create([
                'book_id' => $book->id, 'member_id' => $member->id, 'loan_date' => $loanDate, 'state' => $state,
                'date_expected' => $state === 'draft' ? null : $loanDate->copy()->addDays((int) ($l['expected_days'] ?? 14)),
                'date_returned' => $state === 'returned' ? today()->subDays((int) ($l['returned_days_ago'] ?? 1)) : null,
            ]);
            $loan->load('book');
            $loan->recalculate();
            $this->bump('loans');
        }
    }

    /* ------------------------------------------------------------------ school set-up */

    private function importSubjects(): void
    {
        foreach ($this->load('subjects') ?? [] as $s) {
            $row = Subject::firstOrCreate(['name' => $s['name']], ['code' => $s['code'] ?? null]);
            $this->bump('subjects', $row->wasRecentlyCreated ? 'created' : 'skipped');
        }
    }

    private function importTeachers(): void
    {
        foreach ($this->load('teachers') ?? [] as $t) {
            if (Teacher::where('employee_no', $t['employee_no'])->exists()) { $this->bump('teachers', 'skipped'); continue; }

            $user = null;
            if (! empty($t['email'])) {
                $user = $this->user($t['email']);
                if (! $user) {
                    [$user] = PersonAccounts::create('teacher', $t['name'], $t['email'], $t['password'] ?? 'password', config('school.teacher_max_books', 10));
                    $user->update(['must_change_password' => false]);
                }
            }
            Teacher::create(['user_id' => $user?->id, 'employee_no' => $t['employee_no'], 'name' => $t['name'], 'phone' => $t['phone'] ?? null,
                'email' => $t['email'] ?? null, 'qualification' => $t['qualification'] ?? null, 'joined_on' => today()->subMonths((int) ($t['joined_months_ago'] ?? 24)), 'status' => 'active']);
            $this->bump('teachers');
        }
    }

    private function importSections(): void
    {
        foreach ($this->load('sections') ?? [] as $s) {
            $grade = GradeLevel::firstOrCreate(['name' => $s['grade']], ['level_order' => (int) filter_var($s['grade'], FILTER_SANITIZE_NUMBER_INT)]);
            $section = Section::updateOrCreate(
                ['grade_level_id' => $grade->id, 'academic_year_id' => $this->year->id, 'name' => $s['name']],
                ['class_teacher_id' => $this->teacher($s['class_teacher'] ?? null)?->id, 'capacity' => $s['capacity'] ?? 40, 'room' => $s['room'] ?? null]);
            $this->bump('sections', $section->wasRecentlyCreated ? 'created' : 'updated');

            foreach ($s['subjects'] ?? [] as $subjectName => $employeeNo) {
                $subject = Subject::firstOrCreate(['name' => $subjectName]);
                SectionSubject::updateOrCreate(['section_id' => $section->id, 'subject_id' => $subject->id], ['teacher_id' => $this->teacher($employeeNo)?->id]);
            }
        }
        $this->sectionCache = [];
    }

    /* ------------------------------------------------------------------ students */

    private function makeStudent(array $a, ?Section $section): ?Student
    {
        if (Student::where('admission_no', $a['admission_no'])->exists()) { $this->bump('students', 'skipped'); return null; }

        $userId = null;
        if (! empty($a['login'])) {
            $user = $this->user($a['login']);
            if (! $user) {
                [$user] = PersonAccounts::create('student', $a['name'], $a['login'], $a['password'] ?? 'password', config('school.student_max_books', 3));
                $user->update(['must_change_password' => false]);
            }
            $userId = $user->id;
        }

        $student = Student::create([
            'user_id' => $userId, 'admission_no' => $a['admission_no'], 'name' => $a['name'], 'gender' => $a['gender'] ?? null,
            'date_of_birth' => $a['date_of_birth'] ?? null, 'phone' => $a['phone'] ?? null, 'address' => $a['address'] ?? 'Addis Ababa',
            'guardian_name' => $a['guardian_name'] ?? null, 'guardian_phone' => $a['guardian_phone'] ?? null, 'guardian_email' => $a['guardian_email'] ?? null,
            'section_id' => $section?->id, 'status' => 'active', 'admitted_on' => today()->subMonths($this->ri('adm' . $a['admission_no'], 1, 30)),
        ]);
        if ($section) Enrollment::firstOrCreate(['student_id' => $student->id, 'academic_year_id' => $this->year->id], ['section_id' => $section->id]);
        $this->bump('students');
        return $student;
    }

    private function importStudents(): void
    {
        $d = $this->load('students');
        if (! $d) return;

        foreach ($d['students'] ?? [] as $a) {
            $this->makeStudent($a, isset($a['section']) ? $this->section($a['section']) : null);
        }

        foreach ($d['generate'] ?? [] as $g) {
            $section = $this->section($g['section']);
            if (! $section) { $this->bump('students', 'unknown class ' . $g['section']); continue; }

            $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', Str::after($g['section'], 'Grade ')));
            $gradeNo = (int) filter_var($g['section'], FILTER_SANITIZE_NUMBER_INT);

            for ($i = 1; $i <= (int) ($g['count'] ?? 10); $i++) {
                $adm = ($g['prefix'] ?? 'DEM') . $code . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $female = $this->r('g' . $adm) < 0.5;
                $pool = $female ? self::FIRST_F : self::FIRST_M;
                $first = $pool[$this->ri('fn' . $adm, 0, count($pool) - 1)];
                $last = self::LAST[$this->ri('ln' . $adm, 0, count(self::LAST) - 1)];
                $guardian = (($this->r('gf' . $adm) < 0.5 ? self::FIRST_M : self::FIRST_F)[$this->ri('gn' . $adm, 0, 17)]) . ' ' . $last;

                $this->makeStudent([
                    'admission_no' => $adm, 'name' => "{$first} {$last}", 'gender' => $female ? 'female' : 'male',
                    'date_of_birth' => today()->subYears(6 + $gradeNo)->subDays($this->ri('dob' . $adm, 0, 300))->toDateString(),
                    'guardian_name' => $guardian, 'guardian_phone' => '+2519' . str_pad((string) $this->ri('ph' . $adm, 0, 99999999), 8, '0', STR_PAD_LEFT),
                ], $section);
            }
        }

        // give logged-in students something under "Continue reading"
        $ebooks = Book::released()->where('is_ebook', true)->orderBy('id')->get();
        if ($ebooks->isNotEmpty()) {
            foreach (Student::whereNotNull('user_id')->get() as $i => $s) {
                foreach ([0, 1] as $k) {
                    $book = $ebooks[($i + $k) % $ebooks->count()];
                    Viewer::firstOrCreate(['user_id' => $s->user_id, 'book_id' => $book->id],
                        ['current_page' => $this->ri("vp{$i}{$k}", 1, max($book->total_pages, 2)), 'last_read_at' => now()->subDays($k)]);
                }
            }
        }
    }

    /* ------------------------------------------------------------------ timetable */

    private function importTimetable(): void
    {
        $d = $this->load('timetable');
        if (! $d) return;

        // explicit slots: [{"section": "Grade 5-A", "slots": {"1": {"1": "Mathematics"}}}]  (day => period => subject)
        foreach ($d['sections'] ?? [] as $row) {
            $section = $this->section($row['section']);
            if (! $section) continue;
            foreach ($row['slots'] ?? [] as $day => $periods) {
                foreach ($periods as $period => $subjectName) {
                    $subject = Subject::where('name', $subjectName)->first();
                    $assignment = $subject ? SectionSubject::where('section_id', $section->id)->where('subject_id', $subject->id)->first() : null;
                    if (! $assignment) continue;
                    TimetableSlot::updateOrCreate(['section_id' => $section->id, 'day_of_week' => $day, 'period_no' => $period],
                        ['subject_id' => $subject->id, 'teacher_id' => $assignment->teacher_id]);
                    $this->bump('timetable');
                }
            }
        }

        // automatic: rotate each class's subjects over the week without double-booking any teacher
        if (! empty($d['auto'])) {
            $perDay = min((int) ($d['periods_per_day'] ?? 6), count(config('school.periods')));
            foreach (Section::with('assignments')->where('academic_year_id', $this->year->id)->orderBy('id')->get() as $si => $section) {
                $assign = $section->assignments->values();
                if ($assign->isEmpty() || TimetableSlot::where('section_id', $section->id)->exists()) continue;

                foreach (array_keys(config('school.days')) as $day) {
                    for ($p = 1; $p <= $perDay; $p++) {
                        for ($try = 0; $try < $assign->count(); $try++) {
                            $a = $assign[($day + $p + $si + $try) % $assign->count()];
                            if ($a->teacher_id && TimetableSlot::where('teacher_id', $a->teacher_id)->where('day_of_week', $day)->where('period_no', $p)->exists()) continue;
                            TimetableSlot::create(['section_id' => $section->id, 'day_of_week' => $day, 'period_no' => $p, 'subject_id' => $a->subject_id, 'teacher_id' => $a->teacher_id]);
                            $this->bump('timetable');
                            break;
                        }
                    }
                }
            }
        }
    }

    /* ------------------------------------------------------------------ exams + marks */

    private function importExams(): void
    {
        foreach ($this->load('exams') ?? [] as $e) {
            $term = ! empty($e['term']) ? Term::where('academic_year_id', $this->year->id)->where('name', $e['term'])->first() : null;
            $exam = Exam::firstOrCreate(['academic_year_id' => $this->year->id, 'name' => $e['name']],
                ['term_id' => $term?->id, 'max_marks' => $e['max_marks'] ?? 100, 'is_published' => (bool) ($e['published'] ?? false),
                 'start_date' => today()->subDays((int) ($e['started_days_ago'] ?? 20)), 'end_date' => today()->subDays((int) ($e['ended_days_ago'] ?? 14))]);
            $this->bump('exams', $exam->wasRecentlyCreated ? 'created' : 'skipped');
        }
    }

    private function importMarks(): void
    {
        $d = $this->load('marks');
        if (! $d) return;
        $now = now()->toDateTimeString();

        // explicit rows
        foreach ($d['marks'] ?? [] as $m) {
            $exam = Exam::where('academic_year_id', $this->year->id)->where('name', $m['exam'])->first();
            $student = Student::where('admission_no', $m['student'])->first();
            $subject = Subject::where('name', $m['subject'])->first();
            if (! $exam || ! $student || ! $subject || ! $student->section_id) { $this->bump('marks', 'skipped'); continue; }

            $row = ExamMark::firstOrCreate(['exam_id' => $exam->id, 'student_id' => $student->id, 'subject_id' => $subject->id],
                ['section_id' => $student->section_id, 'marks' => $m['marks'], 'remark' => $m['remark'] ?? null]);
            $this->bump('marks', $row->wasRecentlyCreated ? 'created' : 'skipped');
        }

        // generated: every student gets a stable "strength" plus per-exam noise
        $g = $d['generate'] ?? null;
        if (! $g) return;
        $mean = (float) ($g['mean'] ?? 70);
        $spread = (float) ($g['spread'] ?? 15);
        $missing = (float) ($g['missing_rate'] ?? 0);

        foreach ($g['exams'] ?? [] as $examName) {
            $exam = Exam::where('academic_year_id', $this->year->id)->where('name', $examName)->first();
            if (! $exam) continue;

            $have = ExamMark::where('exam_id', $exam->id)->get(['student_id', 'subject_id'])->map(fn ($m) => $m->student_id . '|' . $m->subject_id)->flip();
            $rows = [];

            foreach (Section::with('assignments.teacher')->where('academic_year_id', $this->year->id)->get() as $section) {
                $students = Student::where('section_id', $section->id)->where('status', 'active')->get(['id']);
                foreach ($section->assignments as $a) {
                    foreach ($students as $s) {
                        $key = $s->id . '|' . $a->subject_id;
                        if ($have->has($key) || $this->r("miss{$exam->id}-{$key}") < $missing) continue;

                        $pct = $mean + ($this->r("ab{$s->id}") - .5) * $spread * 1.6 + ($this->r("n{$exam->id}-{$key}") - .5) * $spread;
                        $pct = max(25, min(100, $pct));
                        $rows[] = ['exam_id' => $exam->id, 'student_id' => $s->id, 'subject_id' => $a->subject_id, 'section_id' => $section->id,
                            'marks' => round($pct / 100 * $exam->max_marks, 1), 'remark' => null, 'entered_by' => $a->teacher?->user_id, 'created_at' => $now, 'updated_at' => $now];
                    }
                }
            }
            foreach (array_chunk($rows, 400) as $chunk) ExamMark::insert($chunk);
            $this->bump('marks', 'created', count($rows));
        }
    }

    /* ------------------------------------------------------------------ attendance */

    private function importAttendance(): void
    {
        $g = ($this->load('attendance') ?? [])['generate'] ?? null;
        if (! $g) return;

        $absent = (float) ($g['absent_rate'] ?? 0.06);
        $late = (float) ($g['late_rate'] ?? 0.05);
        $excused = (float) ($g['excused_rate'] ?? 0.02);

        $dates = [];
        $cursor = today();
        while (count($dates) < (int) ($g['days'] ?? 20)) {
            if ($cursor->isWeekday()) $dates[] = $cursor->copy();
            $cursor->subDay();
        }
        $dates = array_reverse($dates);
        $now = now()->toDateTimeString();

        foreach (Section::with('classTeacher')->where('academic_year_id', $this->year->id)->get() as $section) {
            $ids = Student::where('section_id', $section->id)->where('status', 'active')->pluck('id');
            if ($ids->isEmpty()) continue;

            $have = Attendance::where('section_id', $section->id)->whereDate('date', '>=', $dates[0]->toDateString())->get(['student_id', 'date'])
                ->map(fn ($a) => $a->student_id . '|' . $a->date->toDateString())->flip();

            $rows = [];
            foreach ($dates as $date) {
                $day = $date->toDateString();
                foreach ($ids as $sid) {
                    if ($have->has($sid . '|' . $day)) continue;
                    $r = $this->r("att{$sid}{$day}");
                    $status = $r < $absent ? 'absent' : ($r < $absent + $late ? 'late' : ($r < $absent + $late + $excused ? 'excused' : 'present'));
                    $rows[] = ['student_id' => $sid, 'section_id' => $section->id, 'date' => $day, 'status' => $status, 'remark' => null,
                        'marked_by' => $section->classTeacher?->user_id, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            foreach (array_chunk($rows, 400) as $chunk) Attendance::insert($chunk);
            $this->bump('attendance', 'created', count($rows));
        }
    }

    /* ------------------------------------------------------------------ fees */

    private function importFees(): void
    {
        $d = $this->load('fees');
        if (! $d) return;

        foreach ($d['fee_types'] ?? [] as $f) {
            FeeType::firstOrCreate(['name' => $f['name']], ['amount' => $f['amount'] ?? 0, 'description' => $f['description'] ?? null]);
        }

        $g = $d['generate'] ?? null;
        if (! $g) return;

        $term = Term::where('academic_year_id', $this->year->id)->where('name', $g['term'] ?? 'Term 1')->first();
        $types = FeeType::whereIn('name', $g['fee_types'] ?? [])->get();
        if ($types->isEmpty()) return;

        $desc = ($term->name ?? 'Term') . ' fees';
        $paidRate = (float) ($g['paid_rate'] ?? 0.5);
        $partialRate = (float) ($g['partial_rate'] ?? 0.2);

        foreach (Student::where('status', 'active')->whereNotNull('section_id')->get() as $s) {
            if (FeeInvoice::where('student_id', $s->id)->where('description', $desc)->exists()) { $this->bump('fees', 'skipped'); continue; }

            $total = (float) $types->sum('amount');
            $overdue = $this->r("od{$s->id}") < (float) ($g['overdue_rate'] ?? 0.15);

            $invoice = FeeInvoice::create(['student_id' => $s->id, 'academic_year_id' => $this->year->id, 'term_id' => $term?->id, 'description' => $desc, 'total' => $total,
                'due_date' => $overdue ? today()->subDays($this->ri("odd{$s->id}", 3, 25)) : today()->addDays((int) ($g['due_days'] ?? 30))]);
            foreach ($types as $t) $invoice->items()->create(['fee_type_id' => $t->id, 'description' => $t->name, 'amount' => $t->amount]);

            $r = $this->r("pay{$s->id}");
            $amount = $r < $paidRate ? $total : ($r < $paidRate + $partialRate ? round($total * (0.3 + $this->r("pp{$s->id}") * 0.4), 2) : 0);
            if ($amount > 0) {
                $invoice->payments()->create(['amount' => $amount, 'paid_on' => today()->subDays($this->ri("pd{$s->id}", 1, 20)),
                    'method' => ['cash', 'bank', 'mobile'][$this->ri("pm{$s->id}", 0, 2)]]);
            }
            $invoice->refreshStatus();
            $this->bump('fees');
        }
    }

    /* ------------------------------------------------------------------ announcements */

    private function importAnnouncements(): void
    {
        foreach ($this->load('announcements') ?? [] as $a) {
            $row = Announcement::firstOrCreate(['title' => $a['title']], ['body' => $a['body'], 'audience' => $a['audience'] ?? 'all', 'is_published' => (bool) ($a['published'] ?? true)]);
            $this->bump('announcements', $row->wasRecentlyCreated ? 'created' : 'skipped');
        }
    }
}
