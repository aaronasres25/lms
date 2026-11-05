<?php

namespace App\Console\Commands;

use App\Mail\DailyDigest as DailyDigestMail;
use App\Models\Book;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/** Port of res.users.cron_daily_library_digest */
class DailyDigest extends Command
{
    protected $signature = 'lms:daily-digest';
    protected $description = 'E-mail staff one digest of books released in the last 24 hours';

    public function handle(): int
    {
        $books = Book::with('department')->where('state', 'released')
            ->where('updated_at', '>=', now()->subDay())->orderBy('department_id')->get();

        if ($books->isEmpty()) {
            $this->info('Nothing new - no digest sent.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach (User::staff()->whereNotNull('email')->get() as $user) {
            Mail::to($user->email)->queue(new DailyDigestMail($user, $books));
            $sent++;
        }
        $this->info("Digest queued for {$sent} users ({$books->count()} books).");
        return self::SUCCESS;
    }
}
