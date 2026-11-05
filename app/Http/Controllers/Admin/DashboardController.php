<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\Category;
use App\Models\Department;
use App\Models\Feedback;
use App\Models\Loan;
use App\Models\LoginLog;
use App\Models\Member;
use App\Models\ReadHistory;
use App\Models\User;
use App\Models\Viewer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    /* ------------------------------------------------------------------ helpers */

    /** Last $n calendar months, oldest first (start of month). */
    private function months(int $n = 12): array
    {
        $d = now()->startOfMonth()->subMonthsNoOverflow($n - 1);
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[] = $d->copy();
            $d = $d->addMonthNoOverflow();
        }
        return $out;
    }

    /** Buckets [[date, weight], ...] into the given months -> int[] */
    private function bucket(iterable $rows, array $months): array
    {
        $counts = array_fill_keys(array_map(fn ($m) => $m->format('Y-m'), $months), 0);
        foreach ($rows as [$date, $weight]) {
            if (! $date) continue;
            $k = Carbon::parse($date)->format('Y-m');
            if (isset($counts[$k])) $counts[$k] += $weight;
        }
        return array_values($counts);
    }

    /** Month-over-month change in % (current vs previous month). */
    private function trend(array $series): float
    {
        $cur = (float) end($series);
        $prev = (float) ($series[count($series) - 2] ?? 0);
        if ($prev == 0.0) return $cur > 0 ? 100.0 : 0.0;
        return round(($cur - $prev) / $prev * 100, 1);
    }

    private function one($dates): array
    {
        return collect($dates)->map(fn ($d) => [$d, 1])->all();
    }

    /* ------------------------------------------------------------------ welcome-dashboard feed */

    public function overview(Request $request)
    {
        $months = $this->months(12);
        $start = $months[0];
        $labels = array_map(fn ($m) => $m->format('M'), $months);
        $active = ['issued', 'returned', 'lost'];

        // --- KPI sparklines + trends ---
        $bookSeries = $this->bucket(Book::where('created_at', '>=', $start)->get(['created_at', 'copies_total'])
            ->map(fn ($b) => [$b->created_at, (int) $b->copies_total]), $months);
        $memberSeries = $this->bucket($this->one(Member::where('created_at', '>=', $start)->pluck('created_at')), $months);
        $loanSeries = $this->bucket($this->one(Loan::whereIn('state', $active)->where('loan_date', '>=', $start)->pluck('loan_date')), $months);
        $feedbackSeries = $this->bucket($this->one(Feedback::where('created_at', '>=', $start)->pluck('created_at')), $months);

        // overdue per month of due date: returned late, or still out past the due date
        $today = today();
        $overdueRows = Loan::whereIn('state', $active)->whereNotNull('date_expected')->where('date_expected', '>=', $start)
            ->get(['state', 'date_expected', 'date_returned'])
            ->filter(fn ($l) => ($l->date_returned && $l->date_returned->gt($l->date_expected))
                || ($l->state === 'issued' && $l->date_expected->lt($today)))
            ->map(fn ($l) => [$l->date_expected, 1]);
        $overdueSeries = $this->bucket($overdueRows, $months);

        $kpis = [
            'books'    => ['value' => (int) Book::sum('copies_total'), 'spark' => $bookSeries, 'trend' => $this->trend($bookSeries), 'bad_when_up' => false],
            'members'  => ['value' => Member::count(), 'spark' => $memberSeries, 'trend' => $this->trend($memberSeries), 'bad_when_up' => false],
            'loans'    => ['value' => Loan::whereIn('state', $active)->count(), 'spark' => $loanSeries, 'trend' => $this->trend($loanSeries), 'bad_when_up' => false],
            'overdue'  => ['value' => Loan::where('state', 'issued')->whereNotNull('date_expected')->where('date_expected', '<', $today->toDateString())->count(),
                           'spark' => $overdueSeries, 'trend' => $this->trend($overdueSeries), 'bad_when_up' => true],
            'feedback' => ['value' => Feedback::count(), 'spark' => $feedbackSeries, 'trend' => $this->trend($feedbackSeries), 'bad_when_up' => false],
        ];

        // --- analytics (issued / returned / new members per month) ---
        $analytics = [
            'labels'      => $labels,
            'issued'      => $loanSeries,
            'returned'    => $this->bucket($this->one(Loan::whereNotNull('date_returned')->where('date_returned', '>=', $start)->pluck('date_returned')), $months),
            'new_members' => $memberSeries,
        ];

        // --- top categories (share of titles) ---
        $names = Category::pluck('name', 'id');
        $rows = Book::where('active', true)->selectRaw('category_id, count(*) as c')->groupBy('category_id')->pluck('c', 'category_id')
            ->map(fn ($c, $id) => ['name' => $names[$id] ?? 'Uncategorised', 'count' => (int) $c])->sortByDesc('count')->values();
        $total = max($rows->sum('count'), 1);
        $cats = $rows->take(4)->all();
        if ($others = $rows->slice(4)->sum('count')) $cats[] = ['name' => 'Others', 'count' => (int) $others];
        $cats = array_map(fn ($c) => $c + ['pct' => (int) round($c['count'] / $total * 100)], $cats);

        // --- e-books vs physical (titles) ---
        $ebooks = Book::where('active', true)->where('is_ebook', true)->count();
        $physical = Book::where('active', true)->where('is_ebook', false)->count();
        $all = max($ebooks + $physical, 1);

        // --- recent activity ---
        $icons = ['Book' => ['book', 'bg-emerald-100 text-emerald-600'], 'Loan' => ['loan', 'bg-violet-100 text-violet-600'], 'Member' => ['users', 'bg-sky-100 text-sky-600']];
        $limit = min(max((int) $request->query('limit', 12), 1), 30);
        $activity = ActivityLog::with('user')->latest('id')->limit($limit)->get()->map(function ($a) use ($icons) {
            [$icon, $cls] = $icons[class_basename($a->loggable_type)] ?? ['bolt', 'bg-amber-100 text-amber-600'];
            return [
                'icon' => view('partials.icon', ['name' => $icon, 'class' => 'h-4 w-4'])->render(), 'cls' => $cls,
                'text' => Str::limit($a->body, 90), 'by' => $a->user->name ?? 'System', 'time' => $a->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'kpis' => $kpis, 'analytics' => $analytics, 'categories' => $cats,
            'ebooks' => ['ebooks' => $ebooks, 'physical' => $physical, 'ebook_pct' => (int) round($ebooks / $all * 100), 'physical_pct' => (int) round($physical / $all * 100)],
            'activity' => $activity,
        ]);
    }

    /* ------------------------------------------------------------------ extra insight feeds */

    public function kpis()
    {
        return response()->json([
            'total_books'        => (int) Book::sum('copies_total'),
            'issued_books'       => Loan::where('state', 'issued')->count(),
            'total_members'      => Member::count(),
            'total_system_users' => User::staff()->count(),
            'total_feedback'     => Feedback::count(),
            'overdue'            => Loan::where('state', 'issued')->whereNotNull('date_expected')
                                        ->where('date_expected', '<', today()->toDateString())->count(),
        ]);
    }

    public function charts()
    {
        $deptNames = Department::options();
        $counts = Book::selectRaw('department_id, count(*) as c')->groupBy('department_id')->pluck('c', 'department_id');
        $booksByDepartment = collect($deptNames)->map(fn ($name, $id) => ['department' => $name, 'count' => (int) ($counts[$id] ?? 0)])->values();

        $mostRead = Book::where('is_ebook', true)->where('state', 'released')
            ->orderByDesc('ebook_read_count')->limit(10)->get(['name', 'ebook_read_count'])
            ->map(fn ($b) => ['name' => $b->name, 'count' => $b->ebook_read_count])->values();

        $now = now();
        $issued = Loan::where('state', 'issued')->count();
        $total = (int) Book::sum('copies_total');

        return response()->json([
            'books_by_department' => $booksByDepartment,
            'issued_vs_available' => ['issued' => $issued, 'available' => max($total - $issued, 0)],
            'ebooks_vs_physical'  => ['ebooks' => Book::where('is_ebook', true)->count(), 'physical' => Book::where('is_ebook', false)->count()],
            'most_read_books'     => $mostRead,
            'reading_activity'    => [
                'daily'     => ReadHistory::where('created_at', '>=', $now->copy()->subDay())->count(),
                'weekly'    => ReadHistory::where('created_at', '>=', $now->copy()->subDays(7))->count(),
                'monthly'   => ReadHistory::where('created_at', '>=', $now->copy()->startOfMonth())->count(),
                'quarterly' => ReadHistory::where('created_at', '>=', $now->copy()->subMonths(3))->count(),
                'yearly'    => ReadHistory::where('created_at', '>=', $now->copy()->startOfYear())->count(),
            ],
        ]);
    }

    /** Login report: daily (7 days) / weekly (4 weeks) / monthly (6 months) / annually (3 years). */
    public function loginReport(Request $request)
    {
        $period = $request->query('period', 'daily');
        $today = today();
        $count = fn (Carbon $from, Carbon $to) => LoginLog::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->count();
        $rows = [];

        if ($period === 'weekly') {
            for ($i = 4; $i >= 1; $i--) {
                $start = $today->copy()->subWeeks($i);
                $rows[] = ['period_label' => 'Week ' . $start->format('W'), 'login_count' => $count($start, $start->copy()->addDays(6))];
            }
        } elseif ($period === 'monthly') {
            for ($i = 5; $i >= 0; $i--) {
                $first = $today->copy()->subMonthsNoOverflow($i)->startOfMonth();
                $rows[] = ['period_label' => $first->format('M'), 'login_count' => $count($first, $first->copy()->endOfMonth())];
            }
        } elseif ($period === 'annually') {
            for ($i = 2; $i >= 0; $i--) {
                $y = $today->year - $i;
                $rows[] = ['period_label' => (string) $y, 'login_count' => $count(Carbon::create($y, 1, 1), Carbon::create($y, 12, 31))];
            }
        } else {
            for ($i = 6; $i >= 0; $i--) {
                $d = $today->copy()->subDays($i);
                $rows[] = ['period_label' => $d->format('D'), 'login_count' => $count($d, $d)];
            }
        }
        return response()->json($rows);
    }

    /** "Continue reading" slider for the signed-in user. */
    public function recentReads(Request $request)
    {
        $items = Viewer::with('book:id,name,cover_path,state,active')->where('user_id', $request->user()->id)
            ->whereHas('book', fn ($b) => $b->where('active', true)->where('state', '!=', 'obsolete'))
            ->latest('last_read_at')->limit(10)->get()
            ->map(fn ($v) => [
                'book_id' => $v->book_id, 'name' => $v->book->name, 'cover' => $v->book->cover_url,
                'current_page' => $v->current_page, 'last_read' => $v->last_read_at?->diffForHumans(),
                'url' => route('ebook.view', $v->book_id),
            ]);
        return response()->json($items);
    }
}
