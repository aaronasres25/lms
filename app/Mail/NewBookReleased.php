<?php

namespace App\Mail;

use App\Models\Book;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewBookReleased extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Book $book, public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '📚 New Document: ' . $this->book->name . ' | ' . config('lms.company_name'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.new-book');
    }
}
