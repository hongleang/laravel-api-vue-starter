<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Session cookies and CSRF for requests from the SPA's stateful domains (Sanctum).
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'sanctum/*') || $request->expectsJson(),
        );

        // Every API error is JSON with a readable "message". Validation errors (422) keep
        // Laravel's {"message", "errors": {field: [..]}} shape, which the SPA's useForm reads.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();

            if (! $request->is('api/*', 'sanctum/*') || $status < 400 || $status === 422) {
                return $response;
            }

            if ($status >= 500 && config('app.debug')) {
                return $response;
            }

            $message = match (true) {
                $status === 401 => 'You need to sign in to do that.',
                $status === 403 => 'You do not have permission to do that.',
                $status === 404 => 'The requested resource could not be found.',
                $status === 419 => 'Your session expired. Please try again.',
                $status === 429 => 'Too many requests. Please slow down and try again.',
                $status >= 500 => 'Something went wrong. Please try again.',
                default => 'The request could not be completed.',
            };

            return response()->json(['message' => $message], $status, $response->headers->all());
        });
    })->create();
