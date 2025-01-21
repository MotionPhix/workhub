<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\EncryptHistoryMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [

      \App\Http\Middleware\HandleInertiaRequests::class,
      \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
      EncryptHistoryMiddleware::class,

    ]);

    $middleware->alias([

      // CUSTOM
      'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
      'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,

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
