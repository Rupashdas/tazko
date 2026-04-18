<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresCapability {
    /**
     * Handle an incoming request.
     *
     * Usage in routes:
     *   ->middleware('capability:settings.view')
     */
    public function handle(Request $request, Closure $next, string ...$capabilities): Response {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // super-admin bypasses all capability checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // User must have ALL of the required capabilities
        foreach ($capabilities as $capability) {
            if (!$user->hasCapability($capability)) {
                return response()->json([
                    'message' => 'Forbidden. You do not have the required permission: ' . $capability,
                    'required_capability' => $capability,
                ], 403);
            }
        }

        return $next($request);
    }
}
