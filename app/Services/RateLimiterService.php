<?php

namespace App\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimiterService
{
  protected $limiter;

  public function __construct(RateLimiter $limiter)
  {
    $this->limiter = $limiter;
  }

  public function tooManyAttempts(string $key, int $maxAttempts = 5, int $decayMinutes = 1): bool
  {
    return $this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes);
  }

  public function hit(string $key, int $decayMinutes = 1): int
  {
    return $this->limiter->hit($key, $decayMinutes);
  }

  public function clear(string $key): void
  {
    $this->limiter->clear($key);
  }

  public function availableIn(string $key): int
  {
    return $this->limiter->availableIn($key);
  }

  public function resolveRequestSignature(Request $request): string
  {
    return hash('sha256',
      $request->ip() . '|' . $request->input('email')
    );
  }

  public function handleLoginRateLimit(Request $request): ?Response
  {
    $key = $this->resolveRequestSignature($request);

    if ($this->tooManyAttempts($key)) {
      return response()->json([
        'message' => 'Too many login attempts. Please try again later.',
        'retry_after' => $this->availableIn($key)
      ], 429);
    }

    $this->hit($key);

    return null;
  }
}
