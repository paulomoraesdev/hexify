<?php

declare(strict_types=1);

/**
 * Framework Global Helper Functions
 *
 * This file contains global helper functions that provide convenient access
 * to framework functionality. These functions serve as a clean API layer
 * over the framework's internal architecture while maintaining proper
 * separation of concerns.
 *
 * All functions follow the framework's architectural principles and delegate
 * to appropriate classes rather than containing business logic themselves.
 *
 * @package Hexify\Support
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 */

use Hexify\Environment\DotEnvReader;

if (!function_exists('env')) {
    /**
     * Get an environment variable value
     *
     * Retrieves the value of an environment variable by key. If the key doesn't
     * exist in the environment configuration, returns the provided default value.
     * This function provides a convenient global access point to environment
     * variables while maintaining the framework's architectural principles.
     *
     * The function automatically handles type conversion, converting string
     * representations of booleans, integers, and null values to their proper
     * PHP types for developer convenience.
     *
     * Examples:
     * - env('APP_DEBUG', false) // Returns boolean true/false
     * - env('DATABASE_PORT', 3306) // Returns integer
     * - env('APP_NAME', 'MyApp') // Returns string
     * - env('NONEXISTENT_KEY') // Returns null
     *
     * @param string $key     The environment variable key to retrieve
     * @param mixed  $default The default value to return if key doesn't exist
     *
     * @return mixed The environment variable value or default value
     *
     * @throws \Hexify\Exceptions\EnvLoadException When environment file cannot be loaded
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @see DotEnvReader For the underlying environment reading implementation
     * @uses DotEnvReader::getInstance() To get the environment reader instance
     * @uses DotEnvReader::get() To retrieve the environment value
     */
    function env(string $key, mixed $default = null): mixed
    {
        $envReader = DotEnvReader::getInstance();
        return $envReader->get($key, $default);
    }
}
