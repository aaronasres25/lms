<?php

namespace App\Console\Commands;

use App\Mail\LoginReminder;
use App\Models\Book;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/** Port of lms.book.cron_check_daily_activity: if nothing was released today, nag staff who haven't logged in. */
class ActivityCheck extends Command
{
    protected $signature = 'lms:activity-check';
    protected $description = 'Remind staff who did not log in today when no book was released today';

    public function handle(): int
    {
        $start = now()->startOfDay();
        if (Book::where('state', 'released')->where('updated_at', '>=', $start)->exists()) {
            $this->info('Books were released today - no reminders needed.');
            return self::SUCCESS;
        }

        $users = User::staff()->whereNotNull('email')
            ->where(fn ($q) => $q->whereNull('last_login_at')->orWhere('last_login_at', '<', $start))->get();

        foreach ($users as $user) {
            Mail::to($user->email)->queue(new LoginReminder($user));
        }
        $this->info("Reminders queued for {$users->count()} users.");
        return self::SUCCESS;
    }
}
