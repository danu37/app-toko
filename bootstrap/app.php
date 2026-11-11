<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();

            if ($status === 419) {
                return back()->with([
                    'error' => 'Sesi halaman telah kedaluwarsa. Silakan kirim ulang formulir Anda.',
                ]);
            }

            $handledStatuses = app()->hasDebugModeEnabled()
                ? [403, 404, 503]
                : [403, 404, 500, 503];

            if (! $request->expectsJson() && in_array($status, $handledStatuses, true)) {
                return Inertia::render('Error', [
                    'status' => $status,
                    'message' => $status === 403 ? $exception->getMessage() : '',
                ])->toResponse($request)->setStatusCode($status);
            }

            return $response;
        });
    })->create();
