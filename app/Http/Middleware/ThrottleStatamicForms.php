<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ThrottleStatamicForms
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldThrottle($request)) {
            return $next($request);
        }

        $key = Str::lower(sprintf(
            'statamic-form:%s:%s',
            $request->ip(),
            $request->path()
        ));

        $maxAttempts = 5;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Too many form submissions. Please wait a moment and try again.',
                    'errors' => [
                        'form' => ['Too many form submissions. Please wait a moment and try again.'],
                    ],
                ], 429)->header('Retry-After', $seconds);
            }

            return back()
                ->withErrors([
                    'form' => 'Too many form submissions. Please wait a moment and try again.',
                ])
                ->withInput()
                ->header('Retry-After', $seconds);
        }

        RateLimiter::hit($key, $decaySeconds);

        return $next($request);
    }

    protected function shouldThrottle(Request $request): bool
    {
        $prefix = trim((string) config('statamic.routes.action', '!'), '/').'/forms/';

        return $request->isMethod('post')
            && Str::startsWith(ltrim($request->path(), '/'), $prefix);
    }
}
