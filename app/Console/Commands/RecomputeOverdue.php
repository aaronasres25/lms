<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Illuminate\Console\Command;

class RecomputeOverdue extends Command
{
    protected $signature = 'lms:recompute-overdue';
    protected $description = 'Recompute overdue days and fines for all issued loans';

    public function handle(): int
    {
        $this->info('Recomputed ' . Loan::recomputeIssued() . ' issued loans.');
        return self::SUCCESS;
    }
}
