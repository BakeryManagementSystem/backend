<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
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
        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:5174',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
            'https://bms-rho-ten.vercel.app',
        ];

        $origin = $request->headers->get('Origin');

        // Check if origin is allowed
        $isAllowed = in_array($origin, $allowedOrigins) ||
                     preg_match('/^https:\/\/bms-.*\.vercel\.app$/', $origin);

        // Handle preflight requests first
        if ($request->isMethod('OPTIONS')) {
            if ($isAllowed) {
                return response()->json([], 200, [
                    'Access-Control-Allow-Origin' => $origin,
                    'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Max-Age' => '86400',
                ]);
            } else {
                // Return 200 for preflight but without allowing the origin
                return response()->json([], 200);
            }
        }

        // For actual requests, only proceed if origin is allowed
        if ($isAllowed) {
            return $next($request)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        // If origin is not allowed, proceed without CORS headers (will be blocked by browser)
        return $next($request);
    }
}
