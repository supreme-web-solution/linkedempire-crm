<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = [
            'https://www.linkedin.com',
            'https://app.linkedempire.com',
        ];
        $isChromeExtension = $origin && str_starts_with($origin, 'chrome-extension://');
        $allowOrigin = $isChromeExtension || in_array($origin, $allowedOrigins, true)
            ? $origin
            : 'https://www.linkedin.com';

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $allowOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, lk-id, X-Organization-Id, Idempotency-Key, X-Requested-With, csrf-token, Accept')
                ->header('Access-Control-Max-Age', '86400'); // 24 hours
        }

        try {
            $response = $next($request);
        } catch (\Exception $e) {
            // Create error response with CORS headers
            $response = response()->json([
                'success' => false,
                'message' => 'Server error occurred',
                'error' => $e->getMessage()
            ], 500);
        }

        // Add CORS headers to all responses (including errors)
        return $response
            ->header('Access-Control-Allow-Origin', $allowOrigin)
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, lk-id, X-Organization-Id, Idempotency-Key, X-Requested-With, csrf-token, Accept')
            ->header('Access-Control-Allow-Credentials', 'false');
    }
} 