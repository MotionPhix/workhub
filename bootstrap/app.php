<?php

use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\EncryptHistoryMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            Route::middleware('web')->group(__DIR__.'/../routes/admin.php');
            Route::middleware('web')->group(__DIR__.'/../routes/manager.php');
            Route::middleware('web')->group(__DIR__.'/../routes/auth.php');
        },
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('/login');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            EncryptHistoryMiddleware::class,
        ]);

        $middleware->api([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        // Global middleware
        $middleware->append(RateLimitMiddleware::class);
        $middleware->append(SecurityHeadersMiddleware::class);
        $middleware->append(\App\Http\Middleware\EnsureUserIsActive::class);

        // Named middleware for specific contexts
        $middleware->alias([
            'rate.limit' => RateLimitMiddleware::class,
            'rate.limit.login' => RateLimitMiddleware::class.':login',
            'rate.limit.registration' => RateLimitMiddleware::class.':registration',
            'rate.limit.password_reset' => RateLimitMiddleware::class.':password_reset',
            'rate.limit.api' => RateLimitMiddleware::class.':api',
            'security.headers' => SecurityHeadersMiddleware::class,
            'role.access' => \App\Http\Middleware\EnsureUserRole::class,
            'redirect.after.login' => \App\Http\Middleware\RedirectAfterLogin::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {

            /*if ($exception instanceof ModelNotFoundException) {
            return Inertia('Errors/404', ['message' => 'Resource not found'])
              ->toResponse($request)
              ->setStatusCode(404);
      }

      if ($exception instanceof AccessDeniedHttpException) {
            return Inertia('Errors/403', ['message' => 'You are not authorized'])
              ->toResponse($request)
              ->setStatusCode(403);
      }

      if ($this->isHttpException($exception)) {
            return match($exception->getStatusCode()) {
              500 => Inertia('Errors/500', ['message' => $exception->getMessage()]),
              403 => Inertia('Errors/403', ['message' => 'Forbidden']),
              404 => Inertia('Errors/404', ['message' => 'Not Found']),
              default => $response
            };
      }*/

            return $response;

        });

    })->create();
