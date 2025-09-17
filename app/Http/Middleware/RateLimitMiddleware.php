<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limiterName = 'default'): Response
    {
        $key = $this->resolveKey($request, $limiterName);

        // Define different rate limit configurations
        $limits = $this->getLimits($limiterName);

        // Check if the request exceeds the rate limit
        if ($this->tooManyAttempts($key, $limits)) {
            return $this->buildResponse($key, $limits);
        }

        // Increment attempts
        RateLimiter::hit($key, $limits['decay']);

        return $next($request);
    }

    /**
     * Resolve the rate limit key
     */
    protected function resolveKey(Request $request, string $limiterName): string
    {
        return match ($limiterName) {
            'login' => $this->loginKey($request),
            'registration' => $this->registrationKey($request),
            'password_reset' => $this->passwordResetKey($request),
            'api' => $this->apiKey($request),
            default => $this->defaultKey($request)
        };
    }

    /**
     * Get rate limit configuration
     */
    protected function getLimits(string $limiterName): array
    {
        return match ($limiterName) {
            'login' => [
                'max_attempts' => 5,
                'decay' => 60, // 1 hour lockout
                'message' => 'Too many login attempts. Please try again in 1 hour.',
            ],
            'registration' => [
                'max_attempts' => 3,
                'decay' => 60, // 1 hour
                'message' => 'Too many registration attempts. Please try again later.',
            ],
            'password_reset' => [
                'max_attempts' => 3,
                'decay' => 60, // 1 hour
                'message' => 'Too many password reset attempts. Please try again later.',
            ],
            'api' => [
                'max_attempts' => 60,
                'decay' => 1, // per minute
                'message' => 'Too many API requests. Please slow down.',
            ],
            default => [
                'max_attempts' => 60,
                'decay' => 1,
                'message' => 'Too many attempts. Please slow down.',
            ]
        };
    }

    /**
     * Check if too many attempts have been made
     */
    protected function tooManyAttempts(string $key, array $limits): bool
    {
        return RateLimiter::tooManyAttempts($key, $limits['max_attempts']);
    }

    /**
     * Build response for rate limit exceeded
     */
    protected function buildResponse(string $key, array $limits): Response
    {
        return response()->json([
            'message' => $limits['message'],
            'retry_after' => RateLimiter::availableIn($key),
        ], 429); // Too Many Requests
    }

    /**
     * Key generation methods
     */
    protected function loginKey(Request $request): string
    {
        return Str::lower($request->input('email')).'|'.$request->ip();
    }

    protected function registrationKey(Request $request): string
    {
        return $request->ip();
    }

    protected function passwordResetKey(Request $request): string
    {
        return $request->input('email').'|'.$request->ip();
    }

    protected function apiKey(Request $request): string
    {
        return $request->user()?->id ?? $request->ip();
    }

    protected function defaultKey(Request $request): string
    {
        return $request->ip();
    }
}
