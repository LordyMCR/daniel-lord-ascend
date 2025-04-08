<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Models\BookCopy;
use App\Models\BorrowRequest;
use App\Models\Borrow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BorrowAPIController extends Controller
{
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|exists:book_copies,barcode',
            'membership_number' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        // Find the book copy
        $bookCopy = BookCopy::where('barcode', $request->barcode)->first();
        if (!$bookCopy) {
            return response()->json(['status' => 'error', 'message' => 'Invalid barcode.'], 400);
        }

        // Find the user
        $user = User::where('membership_number', $request->membership_number)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        // Check for an active borrow request for this book and user
        $borrowRequest = BorrowRequest::where('book_copy_id', $bookCopy->id)
            ->where('user_id', $user->id)
            ->where('requested_until', '>', now())
            ->first();

        if ($borrowRequest) {
            // Process the borrow based on the request
            $borrow = Borrow::create([
                'book_copy_id' => $bookCopy->id,
                'user_id' => $user->id,
                'borrowed_from' => now(),
                'due_date' => now()->addDays(14),
            ]);

            $bookCopy->update(['status' => Status::BORROWED]);

            // Delete as it is now fulfilled
            $borrowRequest->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Book borrowed successfully (fulfilling reservation).',
                'borrow_id' => $borrow->id,
                'book_copy' => $bookCopy->fresh(),
                'borrow' => $borrow->fresh(),
            ], 200);
        } else {
            // No active borrow request, proceed with standard borrow if available
            if ($bookCopy->status !== Status::AVAILABLE || $bookCopy->borrows()->whereNull('returned_at')->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Book copy not available for borrowing.',
                    'code' => 'not_available',
                ], 400);
            }

            $borrow = Borrow::create([
                'book_copy_id' => $bookCopy->id,
                'user_id' => $user->id,
                'borrowed_from' => now(),
                'due_date' => now()->addDays(14),
            ]);

            $bookCopy->update(['status' => Status::BORROWED]);

            return response()->json([
                'status' => 'success',
                'message' => 'Book borrowed successfully.',
                'borrow_id' => $borrow->id,
                'book_copy' => $bookCopy->fresh(),
                'borrow' => $borrow->fresh(),
            ], 200);
        }
    }
}
