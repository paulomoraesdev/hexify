<?php

declare(strict_types=1);

namespace Hexify\Exceptions;

use Exception;

/**
 * Exception thrown when application bootstrap fails
 *
 * This exception is thrown when any part of the application bootstrap
 * process fails. This could include environment loading, configuration
 * parsing, service provider registration, or any other initialization
 * step required for the application to start properly.
 *
 * Follows the Framework's exception handling strategy where specific
 * exceptions are thrown for different failure scenarios during startup.
 *
 * @package Hexify\Exceptions
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 */
class BootstrapException extends Exception
{
    /**
     * The bootstrap step that failed
     *
     * @var string|null The name of the bootstrap step that caused the failure
     * @since 1.0.0
     */
    private ?string $bootstrapStep = null;

    /**
     * Create a new bootstrap exception
     *
     * Creates an exception instance with details about the bootstrap
     * failure. Optionally includes the bootstrap step that caused
     * the failure for better debugging information.
     *
     * @param string          $message       The exception message describing the failure
     * @param integer         $code          The exception code (default: 0)
     * @param \Throwable|null $previous      Previous exception for chaining
     * @param string|null     $bootstrapStep Optional name of the failing bootstrap step
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $bootstrapStep = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->bootstrapStep = $bootstrapStep;
    }

    /**
     * Get the bootstrap step that caused the failure
     *
     * Returns the name of the bootstrap step that failed during
     * application initialization, if available. This provides
     * additional context for debugging startup issues.
     *
     * @return string|null The bootstrap step name or null if not available
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getBootstrapStep(): ?string
    {
        return $this->bootstrapStep;
    }
}
