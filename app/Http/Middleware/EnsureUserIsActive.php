<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive {
    /**
     * Reject any authenticated request where the user's is_active flag is false.
     * The session is also invalidated so the next page load forces a fresh login.
     */
    public function handle(Request $request, Closure $next): Response {
        $user = $request->user();

        if ($user && !$user->is_active) {
            // Destroy the session so the browser cookie no longer works
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'status'  => 'error',
                'code'    => 'account_deactivated',
                'message' => 'Your account has been deactivated. Please contact your administrator.',
            ], 403);
        }

        return $next($request);
    }
}
