<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DailyDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Collection $books) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '⚠️ ' . config('lms.company_name') . ' Daily Library Update');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.daily-digest');
    }
}
