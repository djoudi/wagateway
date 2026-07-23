<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    /**
     * Usage: Route::middleware('feature:bulk_send')
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = auth()->user();

        if (! $user?->plan?->hasFeature($feature)) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'FEATURE_NOT_AVAILABLE',
                    'message' => "Your plan does not include the '{$feature}' feature. Upgrade to unlock it.",
                ],
            ], 403);
        }

        return $next($request);
    }
}
