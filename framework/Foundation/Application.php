<?php

declare(strict_types=1);

namespace Hexify\Foundation;

use Hexify\Interfaces\BootstrapperInterface;
use Hexify\Foundation\Bootstrap\LoadEnvironment;
use Hexify\Exceptions\BootstrapException;
use Hexify\Http\Request;
use Hexify\Http\Response;
use Hexify\Http\RequestHandlerFactory;

/**
 * Core application class for the Hexify framework
 *
 * This class serves as the main entry point and orchestrator for the
 * Hexify framework application. It manages the application lifecycle
 * from bootstrap through runtime, providing a clean facade over the
 * complex initialization process.
 *
 * Implements multiple design patterns:
 * - Facade Pattern: Provides simple interface to complex subsystem
 * - Builder Pattern: Constructs application step by step
 * - Chain of Responsibility: Manages bootstrap sequence
 * - Singleton Pattern: Ensures single application instance
 *
 * The application follows hexagonal architecture principles by maintaining
 * clear separation between core business logic and external adapters.
 *
 * @package Hexify\Foundation
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 *
 * @see BootstrapperInterface For bootstrap contracts
 * @uses LoadEnvironment For environment variable loading
 */
class Application
{
    /**
     * Singleton instance of the application
     *
     * @var Application|null The single instance of the application
     * @since 1.0.0
     */
    private static ?Application $instance = null;

    /**
     * Array of bootstrap classes to execute during initialization
     *
     * @var array<class-string<BootstrapperInterface>> Bootstrap classes in execution order
     * @since 1.0.0
     */
    private array $bootstrappers = [
        LoadEnvironment::class,
    ];

    /**
     * Flag indicating if the application has been bootstrapped
     *
     * @var boolean True if bootstrap process has completed successfully
     * @since 1.0.0
     */
    private bool $bootstrapped = false;

    /**
     * Application base path
     *
     * @var string Absolute path to the application root directory
     * @since 1.0.0
     */
    private string $basePath;

    /**
     * Private constructor to enforce singleton pattern
     *
     * Initializes the application with the base path. The base path
     * is used to resolve relative paths for configuration files,
     * templates, and other application resources.
     *
     * @param string|null $basePath Optional base path (defaults to project root)
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?: $this->getDefaultBasePath();
    }

    /**
     * Create a new application instance
     *
     * Factory method that creates and returns a new application instance.
     * Uses the singleton pattern to ensure only one application instance
     * exists throughout the request lifecycle.
     *
     * @param string|null $basePath Optional base path for the application
     *
     * @return Application The application instance
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public static function create(?string $basePath = null): Application
    {
        if (self::$instance === null) {
            self::$instance = new self($basePath);
        }

        return self::$instance;
    }

    /**
     * Get the singleton application instance
     *
     * Returns the existing application instance if it exists, otherwise
     * creates a new one. This provides global access to the application
     * instance throughout the framework.
     *
     * @return Application The application instance
     *
     * @throws BootstrapException When application hasn't been created yet
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public static function getInstance(): Application
    {
        if (self::$instance === null) {
            throw new BootstrapException(
                'Application instance not created. Call Application::create() first.',
                0,
                null,
                'getInstance'
            );
        }

        return self::$instance;
    }

    /**
     * Bootstrap the application
     *
     * Executes the bootstrap chain to initialize all application components.
     * Each bootstrapper in the chain is responsible for a specific aspect
     * of application initialization (environment, configuration, services, etc.).
     *
     * Uses the Chain of Responsibility pattern to execute bootstrappers
     * in sequence, allowing for modular and extensible initialization.
     *
     * @return Application Returns self for method chaining
     *
     * @throws BootstrapException When any bootstrap step fails
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @uses executeBootstrapper To run individual bootstrap classes
     */
    public function bootstrap(): Application
    {
        if ($this->bootstrapped) {
            return $this;
        }

        foreach ($this->bootstrappers as $bootstrapper) {
            $this->executeBootstrapper($bootstrapper);
        }

        $this->bootstrapped = true;
        return $this;
    }

    /**
     * Run the application
     *
     * Starts the application request handling after bootstrap is complete.
     * This method serves as the main entry point for processing HTTP requests
     * and generating responses using the agnostic request/response system.
     *
     * Request Processing Flow:
     * 1. Capture the incoming HTTP request
     * 2. Use factory to select appropriate handler (REST/GraphQL)
     * 3. Process request through selected handler
     * 4. Send response back to client
     * 5. Handle any errors gracefully
     *
     * The application automatically detects the API paradigm (REST, GraphQL)
     * based on request characteristics and routes to the appropriate handler.
     *
     * @return void
     *
     * @throws BootstrapException When application hasn't been bootstrapped
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @uses Request::capture To create request from PHP superglobals
     * @uses RequestHandlerFactory To select appropriate handler
     * @uses handleException For error response generation
     */
    public function run(): void
    {
        if (!$this->bootstrapped) {
            throw new BootstrapException(
                'Application must be bootstrapped before running. Call bootstrap() first.',
                0,
                null,
                'run'
            );
        }

        try {
            // Capture the incoming HTTP request
            $request = Request::capture();

            // Create factory for handler selection
            $handlerFactory = new RequestHandlerFactory();

            // Select and create appropriate handler based on request
            $handler = $handlerFactory->createHandler($request);

            // Process the request through selected handler
            $response = $handler->handle($request);

            // Send the response to the client
            $response->send();
        } catch (\Throwable $exception) {
            // Handle any errors that occur during request processing
            $errorResponse = $this->handleException($exception);
            $errorResponse->send();
        }
    }

    /**
     * Handle exceptions during request processing
     *
     * Creates appropriate error responses for exceptions that occur during
     * request processing. Provides different levels of detail based on
     * environment configuration for security and debugging purposes.
     *
     * @param \Throwable $exception The exception that occurred
     *
     * @return Response Error response for the exception
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function handleException(\Throwable $exception): Response
    {
        $statusCode = 500;
        $message = 'Internal Server Error';

        // Map specific exceptions to appropriate HTTP status codes
        if ($exception instanceof \RuntimeException) {
            $statusCode = 404;
            $message = 'Not Found';
        }

        $errorData = [
            'error' => true,
            'message' => $message,
            'status' => $statusCode,
        ];

        // Add debug information in development environment
        if (env('APP_DEBUG', false)) {
            $errorData['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        return Response::json($errorData, $statusCode);
    }

    /**
     * Get the application base path
     *
     * Returns the absolute path to the application root directory.
     * This path is used as the base for resolving relative paths
     * to configuration files, templates, and other resources.
     *
     * @return string The application base path
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Check if the application has been bootstrapped
     *
     * Returns true if the bootstrap process has completed successfully,
     * false otherwise. This can be used to determine if the application
     * is ready to handle requests.
     *
     * @return boolean True if bootstrapped, false otherwise
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function isBootstrapped(): bool
    {
        return $this->bootstrapped;
    }

    /**
     * Execute a single bootstrapper
     *
     * Creates an instance of the specified bootstrapper class and
     * executes its bootstrap method. Handles any exceptions that
     * occur during the bootstrap process.
     *
     * @param string $bootstrapperClass The bootstrapper class to execute
     *
     * @return void
     *
     * @throws BootstrapException When bootstrapper execution fails
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function executeBootstrapper(string $bootstrapperClass): void
    {
        try {
            /** @var BootstrapperInterface $bootstrapper */
            $bootstrapper = new $bootstrapperClass();
            $bootstrapper->bootstrap($this);
        } catch (BootstrapException $e) {
            // Re-throw bootstrap exceptions as-is
            throw $e;
        } catch (\Throwable $e) {
            throw new BootstrapException(
                "Bootstrap step failed: {$bootstrapperClass} - {$e->getMessage()}",
                0,
                $e,
                $bootstrapperClass
            );
        }
    }

    /**
     * Get the default base path for the application
     *
     * Calculates the default base path by going up two directories
     * from the framework directory to reach the project root.
     *
     * @return string The default application base path
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function getDefaultBasePath(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Prevent cloning of the singleton instance
     *
     * @return void
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function __clone(): void
    {
        // Prevent cloning
    }

    /**
     * Prevent unserialization of the singleton instance
     *
     * @throws BootstrapException Always throws exception
     *
     * @return void
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __wakeup(): void
    {
        throw new BootstrapException('Cannot unserialize Application singleton');
    }
}
