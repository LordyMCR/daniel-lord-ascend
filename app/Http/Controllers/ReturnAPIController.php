<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\BookCopy;
use App\Models\Borrow;
use Illuminate\Support\Facades\Validator;

class ReturnAPIController extends Controller
{
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|exists:book_copies,barcode',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        // Find the book copy
        $bookCopy = BookCopy::where('barcode', $request->barcode)
            ->where('status', Status::BORROWED)
            ->whereHasActiveBorrow()
            ->first();

        if (!$bookCopy) {
            return response()->json([
                'status' => 'error',
                'message' => 'Book copy not borrowed or already returned.',
                'code' => 'not_borrowed',
            ], 400);
        }

        // Find the user
        $user = User::where('membership_number', $request->membership_number)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
                'code' => 'user_not_found',
            ], 404);
        }

        // Check for an active borrow for this book copy and user
        $borrow = Borrow::where('book_copy_id', $bookCopy->id)
            ->where('user_id', $user->id)
            ->whereNull('returned_at')
            ->first();

        if (!$borrow) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active borrow found for this book copy and user.',
                'code' => 'no_active_borrow',
            ], 404);
        }

        // Process the return if borrow exists
        $borrow->update([
            'returned_at' => now(),
        ]);

        // Update the book copy status to available
        $bookCopy->update([
            'status' => Status::AVAILABLE,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Book copy returned successfully.',
            'book_copy' => $bookCopy->fresh(),
            'borrow' => $borrow->fresh(),
        ], 200);
    }
}
