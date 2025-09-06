<?php

declare(strict_types=1);

namespace Hexify\Environment;

use Hexify\Interfaces\EnvReaderInterface;
use Hexify\Exceptions\EnvLoadException;

/**
 * Environment reader for .env files
 *
 * This class provides functionality to read and parse .env files following
 * the dotenv format specification. It implements the EnvReaderInterface
 * contract as part of our hexagonal architecture, serving as an adapter
 * between .env files and the framework's environment system.
 *
 * Implements the Singleton pattern to ensure only one instance manages
 * the environment variables throughout the application lifecycle.
 *
 * Supports:
 * - Key=Value parsing
 * - Quoted values (single and double quotes)
 * - Comments (lines starting with #)
 * - Empty lines (ignored)
 * - Type conversion (string, bool, int, null)
 *
 * @package Hexify\Environment
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 *
 * @see EnvReaderInterface For the contract this class implements
 */
class DotEnvReader implements EnvReaderInterface
{
    /**
     * Singleton instance of the DotEnvReader
     *
     * @var DotEnvReader|null The single instance of this class
     * @since 1.0.0
     */
    private static ?DotEnvReader $instance = null;

    /**
     * Array to store loaded environment variables
     *
     * @var array<string, mixed> Cached environment variables
     * @since 1.0.0
     */
    private array $variables = [];

    /**
     * Path to the .env file
     *
     * @var string Path to the environment file to read
     * @since 1.0.0
     */
    private string $filePath;

    /**
     * Flag indicating if environment has been loaded
     *
     * @var boolean True if load() has been called successfully
     * @since 1.0.0
     */
    private bool $loaded = false;

    /**
     * Private constructor to prevent direct instantiation
     *
     * Implements Singleton pattern by making constructor private.
     * The .env file path is set to the project root by default.
     *
     * @param string $filePath Optional path to .env file
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function __construct(string $filePath = '')
    {
        $this->filePath = $filePath ?: $this->getDefaultEnvPath();
    }

    /**
     * Get the singleton instance of DotEnvReader
     *
     * Returns the single instance of DotEnvReader, creating it if necessary.
     * This ensures only one instance manages environment variables throughout
     * the application, preventing multiple file reads and ensuring consistency.
     *
     * @param string $filePath Optional path to .env file (used only on first call)
     *
     * @return DotEnvReader The singleton instance
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public static function getInstance(string $filePath = ''): DotEnvReader
    {
        if (self::$instance === null) {
            self::$instance = new self($filePath);
        }

        return self::$instance;
    }

    /**
     * Retrieve an environment variable value by key
     *
     * Returns the value of the specified environment variable key.
     * If the key doesn't exist, returns the provided default value.
     * Automatically converts string values to appropriate types (bool, int, null).
     *
     * @param string $key     The environment variable key to retrieve
     * @param mixed  $default The default value to return if key doesn't exist
     *
     * @return mixed The environment variable value or default value
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @uses convertValue To convert string values to appropriate types
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->loaded) {
            $this->load();
        }

        if (!$this->has($key)) {
            return $default;
        }

        return $this->convertValue($this->variables[$key]);
    }

    /**
     * Check if an environment variable exists
     *
     * Determines whether the specified environment variable key exists
     * in the loaded environment data, regardless of its value.
     *
     * @param string $key The environment variable key to check
     *
     * @return boolean True if the key exists, false otherwise
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function has(string $key): bool
    {
        if (!$this->loaded) {
            $this->load();
        }

        return array_key_exists($key, $this->variables);
    }

    /**
     * Load environment variables from .env file
     *
     * Reads and parses the .env file, storing variables in memory for
     * subsequent access. This method should be called once during
     * application bootstrap.
     *
     * @throws EnvLoadException When .env file cannot be read or parsed
     *
     * @return void
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @uses parseEnvFile To parse the file contents
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        if (!file_exists($this->filePath)) {
            throw new EnvLoadException(
                "Environment file not found: {$this->filePath}",
                0,
                null,
                $this->filePath
            );
        }

        if (!is_readable($this->filePath)) {
            throw new EnvLoadException(
                "Environment file is not readable: {$this->filePath}",
                0,
                null,
                $this->filePath
            );
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            throw new EnvLoadException(
                "Failed to read environment file: {$this->filePath}",
                0,
                null,
                $this->filePath
            );
        }

        $this->variables = $this->parseEnvFile($content);
        $this->loaded = true;
    }

    /**
     * Get the default path to the .env file
     *
     * Returns the default path to the .env file, which is located
     * in the project root directory (two levels up from the framework).
     *
     * @return string The default .env file path
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function getDefaultEnvPath(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
    }

    /**
     * Parse the contents of an .env file
     *
     * Processes the raw content of an .env file, extracting key-value pairs
     * while handling comments, empty lines, and quoted values according
     * to the dotenv specification.
     *
     * @param string $content The raw content of the .env file
     *
     * @return array<string, string> Parsed environment variables
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @uses parseLine To process individual lines
     */
    private function parseEnvFile(string $content): array
    {
        $variables = [];
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parsed = $this->parseLine($line, $lineNumber + 1);
            [$key, $value] = $parsed;
            $variables[$key] = $value;
        }

        return $variables;
    }

    /**
     * Parse a single line from the .env file
     *
     * Processes an individual line from the .env file, extracting the key-value
     * pair and handling quoted values, escapes, and validation.
     *
     * @param string  $line       The line to parse
     * @param integer $lineNumber The line number (for error reporting)
     *
     * @return array{0: string, 1: string} Array with [key, value]
     *
     * @throws EnvLoadException When line format is invalid
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function parseLine(string $line, int $lineNumber): array
    {
        if (!str_contains($line, '=')) {
            throw new EnvLoadException(
                "Invalid .env format at line {$lineNumber}: missing '=' separator",
                0,
                null,
                $this->filePath
            );
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            throw new EnvLoadException(
                "Invalid .env format at line {$lineNumber}: empty key",
                0,
                null,
                $this->filePath
            );
        }

        // Handle quoted values
        $value = $this->unquoteValue($value);

        return [$key, $value];
    }

    /**
     * Remove quotes from environment values
     *
     * Removes surrounding single or double quotes from environment values
     * and handles basic escape sequences within quoted strings.
     *
     * @param string $value The value to unquote
     *
     * @return string The unquoted value
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function unquoteValue(string $value): string
    {
        $length = strlen($value);

        if ($length >= 2) {
            $firstChar = $value[0];
            $lastChar = $value[$length - 1];

            if (
                ($firstChar === '"' && $lastChar === '"') ||
                ($firstChar === "'" && $lastChar === "'")
            ) {
                $value = substr($value, 1, -1);

                // Handle escape sequences in double quotes
                if ($firstChar === '"') {
                    $value = str_replace(['\\n', '\\t', '\\"', '\\\\'], ["\n", "\t", '"', '\\'], $value);
                }
            }
        }

        return $value;
    }

    /**
     * Convert string values to appropriate types
     *
     * Automatically converts string representations of boolean, integer,
     * and null values to their proper PHP types for convenience.
     *
     * @param string $value The string value to convert
     *
     * @return mixed The converted value
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function convertValue(string $value): mixed
    {
        // Convert boolean values
        $lowerValue = strtolower($value);
        if (in_array($lowerValue, ['true', 'yes', 'on', '1'], true)) {
            return true;
        }
        if (in_array($lowerValue, ['false', 'no', 'off', '0', ''], true)) {
            return false;
        }

        // Convert null values
        if (in_array($lowerValue, ['null', 'nil'], true)) {
            return null;
        }

        // Convert integer values
        if (is_numeric($value) && (string)(int)$value === $value) {
            return (int)$value;
        }

        // Convert float values
        if (is_numeric($value)) {
            return (float)$value;
        }

        return $value;
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
     * @throws EnvLoadException Always throws exception
     *
     * @return void
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __wakeup(): void
    {
        throw new EnvLoadException('Cannot unserialize DotEnvReader singleton');
    }
}
