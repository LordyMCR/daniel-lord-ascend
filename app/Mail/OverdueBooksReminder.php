<?php

namespace App\Mail;

use App\Models\Book;
use App\Models\Borrow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Inertia\Inertia;

class OverdueBooksReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $book;
    public $borrow;

    public function __construct(User $user, Book $book, Borrow $borrow)
    {
        $this->user = $user;
        $this->book = $book;
        $this->borrow = $borrow;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Overdue Book - ' . $this->book->name,
        );
    }

    public function content(): Content
    {
        $renderedComponent = Inertia::render('Emails/OverdueBookReminderEmail', [
            'userName' => $this->user->name,
            'bookTitle' => $this->book->name,
            'dueDate' => $this->borrow->due_date->format('l, jS F Y'),
            'appName' => config('app.name'),
        ])->render();

        return new Content(
            html: $renderedComponent,
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
