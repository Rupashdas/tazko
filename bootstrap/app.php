<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureProjectMember;
use App\Http\Middleware\EnsureProjectNotArchived;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RequiresCapability;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->alias([
            'capability'           => RequiresCapability::class,
            'active'               => EnsureUserIsActive::class,
            'project.member'       => EnsureProjectMember::class,
            'project.not_archived' => EnsureProjectNotArchived::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
