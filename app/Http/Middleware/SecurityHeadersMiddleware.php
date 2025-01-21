<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
  public function handle(Request $request, Closure $next): Response
  {
    $response = $next($request);

    // Only add headers for successful responses
    if ($response->getStatusCode() === 200) {
      $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
      $response->headers->set('X-Content-Type-Options', 'nosniff');
      $response->headers->set('X-XSS-Protection', '1; mode=block');

      // Strict transport security (only for HTTPS)
      if ($request->secure()) {
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
      }

      // Referrer policy
      $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

      // Content Security Policy
//      $response->headers->set('Content-Security-Policy',
//        "default-src 'self'; script-src 'self' platform.twitter.com 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' * data:; font-src 'self' data: ; connect-src 'self'; media-src 'self'; frame-src 'self' platform.twitter.com github.com *.youtube.com *.vimeo.com; object-src 'none'; base-uri 'self'; report-uri "
//      );
    }

    return $response;
  }
}
