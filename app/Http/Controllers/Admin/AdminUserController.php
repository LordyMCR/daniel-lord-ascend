<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Borrow;
use App\Mail\OverdueBooksReminder;
use App\Enums\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class AdminUserController extends Controller
{
    public function showOverdueBorrows(): Response
    {
        $now = Carbon::now();

        // Fetch all general users with overdue books
        $overdueUsers = User::where('user_type', UserType::GENERAL)
            ->whereHas('borrows', function ($query) use ($now) {
                $query->where('due_date', '<', $now)
                    ->whereNull('returned_at');
            })
            ->orderBy('name')
            ->get();

        // Eager load the borrows relationship with book details
        foreach ($overdueUsers as $user) {
            $user->setRelation('borrows', $user->borrows()
                ->where('due_date', '<', $now)
                ->whereNull('returned_at')
                ->with('bookCopy.book')
                ->get());
        }

        // Check if there are any overdue users
        if ($overdueUsers->isEmpty()) {
            return Inertia::render('Admin/OverdueUsers', [
                'overdueUsers' => $overdueUsers,
                'message' => 'No users currently have overdue books.',
            ]);
        }

        return Inertia::render('Admin/OverdueUsers', [
            'overdueUsers' => $overdueUsers,
            'message' => null,
        ]);
    }

    public function sendOverdueReminder(Request $request, Borrow $borrow): RedirectResponse
    {
        $borrow->load('user', 'bookCopy.book');
        $user = $borrow->user;

        // Check if the user is a general user
        if ($user->user_type !== UserType::GENERAL) {
            return Redirect::route('admin.overdue')
            ->with('warning', 'User "' . $user->name . '" is not a GENERAL user. Cannot send reminder.');
        }

        // Check if the book is overdue
        if ($borrow->due_date >= Carbon::now() || $borrow->returned_at) {
            return Redirect::route('admin.overdue')
            ->with('warning', 'Book "' . $borrow->bookCopy->book->name . '" is no longer overdue or has been returned.');
        }

        // Try to send the email, log any errors
        try {
            Mail::to($user->email)->send(new OverdueBooksReminder($user, $borrow->bookCopy->book, $borrow));

            return Redirect::route('admin.overdue')
                ->with('success', 'Reminder email sent successfully for "' . $borrow->bookCopy->book->name . '" to ' . $user->name . '.');
        } catch (\Exception $e) {
            Log::error("Failed to send overdue reminder for borrow ID {$borrow->id} (Book: {$borrow->bookCopy->book->name}, User: {$user->email}): " . $e->getMessage());
            return Redirect::route('admin.overdue')
                ->with('error', 'Failed to send reminder email for "' . $borrow->bookCopy->book->name . '". Please check system logs.');
        }

    }

    public function sendBulkOverdueReminders(Request $request) : RedirectResponse
    {
        // Get the list of all overdue borrows
        $overdueBorrows = Borrow::where('due_date', '<', Carbon::now())
            ->whereNull('returned_at')
            ->with('user', 'bookCopy.book')
            ->get();

        // Check if there are any overdue borrows
        if ($overdueBorrows->isEmpty()) {
            return Redirect::route('admin.overdue')
                ->with('warning', 'No overdue books found for any users.');
        }

        $sentCount = 0;
        $errorOccurred = false;

        // Loop through each overdue borrow and send the reminder
        foreach ($overdueBorrows as $borrow) {
            $user = $borrow->user;

            // Check if the user is a general user, skip if not
            if ($user->user_type !== UserType::GENERAL) {
                continue;
            }

            // Try to send the email, log any errors
            try {
                Mail::to($user->email)->send(new OverdueBooksReminder($user, $borrow->bookCopy->book, $borrow));
                $sentCount++;
            } catch (\Exception $e) {
                Log::error("Failed to send overdue reminder for borrow ID {$borrow->id} (Book: {$borrow->bookCopy->book->name}, User: {$user->email}): " . $e->getMessage());
                $errorOccurred = true;
            }
        }

        // Check the result of the bulk sending, return appropriate response
        if ($sentCount > 0 && !$errorOccurred) {
            return Redirect::route('admin.overdue')
                ->with('success', 'Successfully sent ' . $sentCount . ' overdue book reminders.');
        } elseif ($sentCount > 0 && $errorOccurred) {
            return Redirect::route('admin.overdue')
                ->with('warning', 'Sent ' . $sentCount . ' overdue book reminders, but some emails may have failed. Check logs for details.');
        } else {
            return Redirect::route('admin.overdue')
                ->with('info', 'No overdue book reminders were sent.');
        }
    }
}
