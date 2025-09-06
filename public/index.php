<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Hexify Framework Bootstrap
|--------------------------------------------------------------------------
|
| This file serves as the entry point for the Hexify framework application.
| It loads the Composer autoloader, creates the application instance,
| and runs the bootstrap sequence to initialize all framework components.
|
*/

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../framework/Support/helpers.php';

use Hexify\Foundation\Application;

try {
    $app = Application::create(__DIR__ . '/..');
    $app->bootstrap();
    $app->run();
} catch (Throwable $e) {
    // Simple error handling for now
    echo "Application failed to start: " . $e->getMessage() . PHP_EOL;
    if (env('APP_DEBUG', false)) {
        echo "Stack trace:" . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
    }
    exit(1);
}
