<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Please login to access this section.');
        }

        $user = Auth::user();

        if (empty($roles) || in_array($user->role, $roles)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access. Your role (' . strtoupper($user->role) . ') does not have permission for this section.');
    }
}
