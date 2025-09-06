<?php

declare(strict_types=1);

namespace Hexify\Interfaces;

/**
 * Contract defining environment variable reading operations
 *
 * This interface defines the port for environment variable access in our
 * hexagonal architecture. Implementations should handle different sources
 * of environment data (.env files, system env vars, etc.) while maintaining
 * a consistent interface for the application layer.
 *
 * Implements the Adapter Pattern to allow different environment sources.
 *
 * @package Hexify\Config
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 */
interface EnvReaderInterface
{
    /**
     * Retrieve an environment variable value by key
     *
     * Reads the environment variable specified by key and returns its value.
     * If the key doesn't exist, returns the provided default value.
     * This method should handle type conversion automatically when possible.
     *
     * @param string $key     The environment variable key to retrieve
     * @param mixed  $default The default value to return if key doesn't exist
     *
     * @return mixed The environment variable value or default value
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @see DotEnvReader For the default .env file implementation
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if an environment variable exists
     *
     * Determines whether the specified environment variable key exists
     * in the current environment configuration, regardless of its value.
     *
     * @param string $key The environment variable key to check
     *
     * @return boolean True if the key exists, false otherwise
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function has(string $key): bool;

    /**
     * Load environment variables from source
     *
     * Initializes and loads environment variables from the configured source.
     * This method should be called once during application bootstrap to
     * populate the environment data for subsequent get() calls.
     *
     * @throws \Hexify\Exceptions\EnvLoadException When environment source cannot be loaded
     *
     * @return void
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function load(): void;
}
