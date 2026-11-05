<?php

namespace App\Traits;

use App\Models\ActivityLog;

/** Replacement for Odoo's chatter (message_post). */
trait LogsActivity
{
    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'loggable')->latest('id');
    }

    public function logActivity(string $body): void
    {
        $this->activities()->create(['user_id' => auth()->id(), 'body' => $body]);
    }
}
