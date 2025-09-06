<?php

declare(strict_types=1);

namespace Hexify\Exceptions;

use Exception;

/**
 * Exception thrown when environment loading fails
 *
 * This exception is thrown when the environment reader cannot load
 * environment variables from the configured source. This could be due
 * to missing files, permission issues, or malformed environment data.
 *
 * Follows the Framework's exception handling strategy where specific
 * exceptions are thrown for different failure scenarios.
 *
 * @package Hexify\Config
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 */
class EnvLoadException extends Exception
{
    /**
     * The path to the environment file that failed to load
     *
     * @var string|null Path to the problematic environment file
     * @since 1.0.0
     */
    private ?string $filePath = null;

    /**
     * Create a new environment loading exception
     *
     * Creates an exception instance with details about the environment
     * loading failure. Optionally includes the file path that caused
     * the failure for better debugging information.
     *
     * @param string         $message  The exception message describing the failure
     * @param integer        $code     The exception code (default: 0)
     * @param Exception|null $previous Previous exception for chaining
     * @param string|null    $filePath Optional path to the failing file
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        ?string $filePath = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->filePath = $filePath;
    }

    /**
     * Get the file path that caused the loading failure
     *
     * Returns the path to the environment file that failed to load,
     * if available. This provides additional context for debugging
     * environment configuration issues.
     *
     * @return string|null The file path or null if not available
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }
}
