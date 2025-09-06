<?php

declare(strict_types=1);

namespace Hexify\Http;

/**
 * HTTP Request abstraction for framework-agnostic request handling
 *
 * This class provides a clean abstraction over HTTP request data, isolating
 * the framework from PHP superglobals and providing a consistent interface
 * for request processing regardless of the API style (REST, GraphQL, etc.).
 *
 * Implements the Adapter pattern to provide a clean interface over HTTP
 * request data while maintaining framework independence and testability.
 *
 * Supports:
 * - Multiple HTTP methods (GET, POST, PUT, DELETE, PATCH, OPTIONS)
 * - Header management with case-insensitive access
 * - Query parameters and request body data
 * - Content type detection and parsing
 * - File upload handling
 * - Request URI and path information
 *
 * @package Hexify\Http
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 */
class Request
{
    /**
     * HTTP method for the request
     *
     * @var string The HTTP method (GET, POST, PUT, DELETE, etc.)
     * @since 1.0.0
     */
    private string $method;

    /**
     * Request URI path
     *
     * @var string The URI path without query string
     * @since 1.0.0
     */
    private string $path;

    /**
     * Query parameters from the URL
     *
     * @var array<string, mixed> Query string parameters
     * @since 1.0.0
     */
    private array $queryParams;

    /**
     * Request body data
     *
     * @var array<string, mixed> Parsed request body data
     * @since 1.0.0
     */
    private array $bodyData;

    /**
     * HTTP headers
     *
     * @var array<string, string> HTTP headers with lowercase keys
     * @since 1.0.0
     */
    private array $headers;

    /**
     * Raw request body
     *
     * @var string Raw request body content
     * @since 1.0.0
     */
    private string $rawBody;

    /**
     * Uploaded files
     *
     * @var array<string, mixed> File upload information
     * @since 1.0.0
     */
    private array $files;

    /**
     * Server variables
     *
     * @var array<string, mixed> Server environment variables
     * @since 1.0.0
     */
    private array $server;

    /**
     * Create a new Request instance
     *
     * Constructs a request object with all HTTP request data. Parameters
     * default to PHP superglobals but can be overridden for testing.
     *
     * @param string                $method  HTTP method
     * @param string                $path    Request path
     * @param array<string, mixed>  $query   Query parameters
     * @param array<string, mixed>  $body    Request body data
     * @param array<string, string> $headers HTTP headers
     * @param string                $rawBody Raw request body
     * @param array<string, mixed>  $files   Uploaded files
     * @param array<string, mixed>  $server  Server variables
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct(
        string $method,
        string $path,
        array $query = [],
        array $body = [],
        array $headers = [],
        string $rawBody = '',
        array $files = [],
        array $server = []
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->queryParams = $query;
        $this->bodyData = $body;
        $this->headers = $this->normalizeHeaders($headers);
        $this->rawBody = $rawBody;
        $this->files = $files;
        $this->server = $server;
    }

    /**
     * Create Request instance from PHP superglobals
     *
     * Factory method that creates a Request instance using data from PHP
     * superglobals ($_GET, $_POST, $_SERVER, etc.). This is the primary
     * way to create Request objects in production.
     *
     * @return Request The created request instance
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @uses parseRequestBody To parse JSON and other request body formats
     */
    public static function capture(): Request
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $query = $_GET;
        $body = $_POST;
        $files = $_FILES;
        $server = $_SERVER;

        // Get raw body for JSON/GraphQL requests
        $rawBody = file_get_contents('php://input') ?: '';

        // Parse JSON body for non-form requests
        if (!empty($rawBody) && empty($body)) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $decoded = json_decode($rawBody, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $body = $decoded;
                }
            }
        }

        // Extract headers
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            }
        }

        return new self($method, $path, $query, $body, $headers, $rawBody, $files, $server);
    }

    /**
     * Get the HTTP method
     *
     * @return string The HTTP method in uppercase
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the request path
     *
     * @return string The request path without query string
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get all query parameters
     *
     * @return array<string, mixed> All query parameters
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Get a specific query parameter
     *
     * @param string $key     The parameter key
     * @param mixed  $default Default value if key doesn't exist
     *
     * @return mixed The parameter value or default
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Get all body data
     *
     * @return array<string, mixed> All request body data
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getBodyData(): array
    {
        return $this->bodyData;
    }

    /**
     * Get a specific body parameter
     *
     * @param string $key     The parameter key
     * @param mixed  $default Default value if key doesn't exist
     *
     * @return mixed The parameter value or default
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getBodyParam(string $key, mixed $default = null): mixed
    {
        return $this->bodyData[$key] ?? $default;
    }

    /**
     * Get all headers
     *
     * @return array<string, string> All HTTP headers
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a specific header value
     *
     * @param string $name    The header name (case-insensitive)
     * @param string $default Default value if header doesn't exist
     *
     * @return string The header value or default
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getHeader(string $name, string $default = ''): string
    {
        $normalizedName = strtolower($name);
        return $this->headers[$normalizedName] ?? $default;
    }

    /**
     * Check if a header exists
     *
     * @param string $name The header name (case-insensitive)
     *
     * @return boolean True if header exists, false otherwise
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function hasHeader(string $name): bool
    {
        $normalizedName = strtolower($name);
        return isset($this->headers[$normalizedName]);
    }

    /**
     * Get the raw request body
     *
     * @return string The raw request body
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    /**
     * Get the request content type
     *
     * @return string The content type or empty string
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getContentType(): string
    {
        return $this->getHeader('content-type');
    }

    /**
     * Check if the request is JSON
     *
     * @return boolean True if content type is JSON
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function isJson(): bool
    {
        return str_contains($this->getContentType(), 'application/json');
    }

    /**
     * Check if the request expects JSON response
     *
     * @return boolean True if client accepts JSON
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function expectsJson(): bool
    {
        $accept = $this->getHeader('accept');
        return str_contains($accept, 'application/json') || str_contains($accept, '*/*');
    }

    /**
     * Get uploaded files
     *
     * @return array<string, mixed> File upload information
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Get server variables
     *
     * @return array<string, mixed> Server environment variables
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getServer(): array
    {
        return $this->server;
    }

    /**
     * Normalize header names to lowercase
     *
     * @param array<string, string> $headers Raw headers array
     *
     * @return array<string, string> Headers with lowercase keys
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower($name)] = $value;
        }
        return $normalized;
    }
}
