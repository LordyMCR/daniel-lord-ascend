<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\UserType;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and is an admin
        if (Auth::check() && Auth::user()->user_type === UserType::ADMIN) {
            return $next($request);
        }

        // Redirect non-admins or guests
        return redirect('/login')->with('error', 'Unauthorized access.');
    }
}
