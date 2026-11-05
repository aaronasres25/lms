<?php

use Illuminate\Support\Facades\Schedule;

// Mirrors the two Odoo ir.cron records (+ the unscheduled activity check, opt-in)
Schedule::command('lms:daily-digest')->dailyAt('08:00');
Schedule::command('lms:recompute-overdue')->daily();

if (config('lms.enable_activity_check')) {
    Schedule::command('lms:activity-check')->dailyAt('16:00');
}
