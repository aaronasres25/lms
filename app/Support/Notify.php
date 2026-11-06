<?php

namespace App\Support;

use App\Models\Loan;
use App\Models\User;
use App\Notifications\LmsNotification;
use Illuminate\Support\Facades\Log;

class Notify
{
    /**
     * @param User|iterable<User> $users
     * @param array $meta extra keys stored with the notification (e.g. ['book_id' => 5]) - used to mark it read
     *                    automatically when the person opens that record.
     */
    public static function send($users, string $title, string $body, string $url, string $icon = 'bell', string $color = 'violet', array $meta = []): void
    {
        $users = $users instanceof User ? collect([$users]) : collect($users);

        foreach ($users->unique('id') as $user) {
            if (! $user->active) continue;
            try {
                $user->notify(new LmsNotification($title, $body, $url, $icon, $color, $meta));
            } catch (\Throwable $e) {
                Log::warning("LMS notification failed for user {$user->id}: {$e->getMessage()}");
            }
        }
    }

    /** A borrower's own reminder (students and teachers have no back-office): once per day. */
    public static function overdueForBorrower(User $user): void
    {
        $member = $user->member;
        if (! $member) return;

        $count = Loan::where('member_id', $member->id)->where('state', 'issued')->whereNotNull('date_expected')
            ->where('date_expected', '<', today()->toDateString())->count();
        if ($count === 0) return;

        $key = 'overdue-me-' . today()->toDateString();
        if ($user->notifications()->where('data', 'like', '%"key":"' . $key . '"%')->exists()) return;

        $url = $user->isStudent() ? route('student.library', [], false) : route('catalog', [], false);
        self::send($user, 'Overdue book', "You have {$count} overdue " . ($count === 1 ? 'book' : 'books') . '. Please return ' . ($count === 1 ? 'it' : 'them') . ' to the library.',
            $url, 'clock', 'orange', ['key' => $key]);
    }

    /** One "N loans are overdue" notification per person per day (no free cron needed - also run at login). */
    public static function overdueDigest(?User $only = null): void
    {
        $count = Loan::where('state', 'issued')->whereNotNull('date_expected')
            ->where('date_expected', '<', today()->toDateString())->count();
        if ($count === 0) return;

        $key = 'overdue-' . today()->toDateString();
        $staff = $only ? collect([$only]) : User::staff()->get();

        foreach ($staff as $user) {
            if (! $user->canDo('loans', 'read')) continue;
            if ($user->notifications()->where('data', 'like', '%"key":"' . $key . '"%')->exists()) continue;

            self::send($user, 'Overdue loans', "{$count} " . ($count === 1 ? 'loan is' : 'loans are') . ' overdue.',
                route('admin.loans.index', ['overdue' => 1], false), 'clock', 'orange', ['key' => $key]);
        }
    }
}
