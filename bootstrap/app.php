<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register PBC middleware aliases
        $middleware->alias([
            'pbc.permission' => \App\Http\Middleware\CheckPbcPermission::class,
            'pbc.auth' => \App\Http\Middleware\PbcAuthenticate::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Priority middleware for PBC
        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\CheckPbcPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handling for PBC
        $exceptions->render(function (\App\Exceptions\PbcPermissionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions for PBC operation',
                'required_permission' => $e->getRequiredPermission()
            ], 403);
        });
    })
    ->withProviders([
        // Register PBC Service Provider
        App\Providers\PbcServiceProvider::class,
    ])
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // PBC Scheduled Commands (Laravel 12 style)
        $schedule->command('pbc:check-overdue')
                 ->dailyAt('09:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/pbc-overdue.log'));

        $schedule->command('pbc:send-reminders')
                 ->dailyAt('08:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/pbc-reminders.log'));

        $schedule->command('pbc:cleanup-logs')
                 ->monthlyOn(1, '02:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/pbc-cleanup.log'));
    })
    ->create();
