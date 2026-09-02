<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\VerifyModuleRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('api', VerifyModuleRequest::class);
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'requestID' => $request->attributes->get('requestID', $request->header('X-Request-ID')),
                'timestamp' => now()->toIso8601String(),
                'status' => 'error',
                'message' => 'The request data is invalid.',
                'errors' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            $message = $code >= 500 ? 'The service could not complete the request.' : ($exception->getMessage() ?: 'The request could not be completed.');

            return response()->json([
                'requestID' => $request->attributes->get('requestID', $request->header('X-Request-ID')),
                'timestamp' => now()->toIso8601String(),
                'status' => 'error',
                'message' => $message,
            ], $code);
        });
    })->create();
