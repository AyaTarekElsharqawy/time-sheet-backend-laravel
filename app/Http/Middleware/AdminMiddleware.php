<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Middleware to restrict routes to admin users only.
// Returns 403 JSON response for non-admins.
class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        return $next($request);
    }
}