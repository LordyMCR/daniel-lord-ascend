<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookCopyResource;
use App\Models\BookCopy;
use Illuminate\Http\Request;

class BookCopyController extends Controller
{
    public function index(Request $request)
    {
        $bookCopies = BookCopy::query()
            ->whereNotReserved()
            ->applySearchFiltersFrom($request)
            ->paginate(10);

        return inertia('BookCopies/Index', [
            'book_copies' => BookCopyResource::collection($bookCopies)->response()->getData(true),
        ]);
    }
}
