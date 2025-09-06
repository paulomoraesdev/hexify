<?php

declare(strict_types=1);

namespace Hexify\Http;

/**
 * HTTP Response abstraction for framework-agnostic response handling
 *
 * This class provides a clean abstraction for HTTP response generation,
 * supporting multiple content types and formats while maintaining
 * framework independence. It handles response building, header management,
 * and content serialization for both REST and GraphQL APIs.
 *
 * Implements the Builder pattern for fluent response construction and
 * supports multiple response formats (JSON, XML, HTML, etc.) through
 * content type negotiation.
 *
 * Features:
 * - HTTP status code management with semantic helpers
 * - Header management with proper HTTP compliance
 * - JSON/XML content serialization
 * - CORS support for cross-origin requests
 * - Method chaining for fluent API design
 * - Content type negotiation
 * - Response caching headers
 *
 * @package Hexify\Http
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 */
class Response
{
    /**
     * HTTP status code
     *
     * @var integer The HTTP status code
     * @since 1.0.0
     */
    private int $statusCode;

    /**
     * HTTP headers
     *
     * @var array<string, string> Response headers
     * @since 1.0.0
     */
    private array $headers;

    /**
     * Response content
     *
     * @var mixed The response content/data
     * @since 1.0.0
     */
    private mixed $content;

    /**
     * Response content type
     *
     * @var string The content type
     * @since 1.0.0
     */
    private string $contentType;

    /**
     * HTTP status code messages
     *
     * @var array<int, string> Mapping of status codes to messages
     * @since 1.0.0
     */
    private const STATUS_MESSAGES = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /**
     * Create a new Response instance
     *
     * Initializes a response with optional content, status code, and headers.
     * Defaults to JSON content type for API responses.
     *
     * @param mixed                 $content     Response content
     * @param integer               $statusCode  HTTP status code
     * @param array<string, string> $headers     Additional headers
     * @param string                $contentType Response content type
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct(
        mixed $content = null,
        int $statusCode = 200,
        array $headers = [],
        string $contentType = 'application/json'
    ) {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->contentType = $contentType;
        $this->headers = array_merge([
            'Content-Type' => $contentType,
            'X-Powered-By' => 'Hexify Framework',
        ], $headers);
    }

    /**
     * Create a JSON response
     *
     * Factory method for creating JSON responses, the most common
     * response type for REST and GraphQL APIs.
     *
     * @param mixed                 $data       Data to encode as JSON
     * @param integer               $statusCode HTTP status code
     * @param array<string, string> $headers    Additional headers
     *
     * @return Response JSON response instance
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public static function json(mixed $data, int $statusCode = 200, array $headers = []): Response
    {
        return new self($data, $statusCode, $headers, 'application/json');
    }

    /**
     * Create an XML response
     *
     * Factory method for creating XML responses for clients that
     * specifically require XML format.
     *
     * @param mixed                 $data       Data to encode as XML
     * @param integer               $statusCode HTTP status code
     * @param array<string, string> $headers    Additional headers
     *
     * @return Response XML response instance
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public static function xml(mixed $data, int $statusCode = 200, array $headers = []): Response
    {
        return new self($data, $statusCode, $headers, 'application/xml');
    }

    /**
     * Create a plain text response
     *
     * @param string                $text       Plain text content
     * @param integer               $statusCode HTTP status code
     * @param array<string, string> $headers    Additional headers
     *
     * @return Response Text response instance
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public static function text(string $text, int $statusCode = 200, array $headers = []): Response
    {
        return new self($text, $statusCode, $headers, 'text/plain');
    }

    /**
     * Create an error response
     *
     * Factory method for creating standardized error responses
     * with consistent structure for both REST and GraphQL APIs.
     *
     * @param string                $message    Error message
     * @param integer               $statusCode HTTP status code
     * @param mixed                 $details    Additional error details
     * @param array<string, string> $headers    Additional headers
     *
     * @return Response Error response instance
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        mixed $details = null,
        array $headers = []
    ): Response {
        $data = [
            'error' => true,
            'message' => $message,
            'status' => $statusCode,
        ];

        if ($details !== null) {
            $data['details'] = $details;
        }

        return self::json($data, $statusCode, $headers);
    }

    /**
     * Set the response status code
     *
     * @param integer $statusCode HTTP status code
     *
     * @return Response Returns self for method chaining
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function setStatusCode(int $statusCode): Response
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get the response status code
     *
     * @return integer The HTTP status code
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set a response header
     *
     * @param string $name  Header name
     * @param string $value Header value
     *
     * @return Response Returns self for method chaining
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function setHeader(string $name, string $value): Response
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers at once
     *
     * @param array<string, string> $headers Headers to set
     *
     * @return Response Returns self for method chaining
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function setHeaders(array $headers): Response
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Get all response headers
     *
     * @return array<string, string> All response headers
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
     * @param string $name    Header name
     * @param string $default Default value if header doesn't exist
     *
     * @return string Header value or default
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getHeader(string $name, string $default = ''): string
    {
        return $this->headers[$name] ?? $default;
    }

    /**
     * Set the response content
     *
     * @param mixed $content The response content
     *
     * @return Response Returns self for method chaining
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function setContent(mixed $content): Response
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the response content
     *
     * @return mixed The response content
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Set the content type
     *
     * @param string $contentType The content type
     *
     * @return Response Returns self for method chaining
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function setContentType(string $contentType): Response
    {
        $this->contentType = $contentType;
        $this->headers['Content-Type'] = $contentType;
        return $this;
    }

    /**
     * Get the content type
     *
     * @return string The content type
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Add CORS headers for cross-origin requests
     *
     * @param string        $origin  Allowed origin (* for all)
     * @param array<string> $methods Allowed HTTP methods
     * @param array<string> $headers Allowed headers
     * @param integer       $maxAge  Preflight cache time in seconds
     *
     * @return Response Returns self for method chaining
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function withCors(
        string $origin = '*',
        array $methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        array $headers = ['Content-Type', 'Authorization', 'X-Requested-With'],
        int $maxAge = 86400
    ): Response {
        $this->setHeaders([
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => implode(', ', $methods),
            'Access-Control-Allow-Headers' => implode(', ', $headers),
            'Access-Control-Max-Age' => (string) $maxAge,
        ]);

        return $this;
    }

    /**
     * Set caching headers
     *
     * @param integer $ttl Time to live in seconds
     *
     * @return Response Returns self for method chaining
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function withCaching(int $ttl): Response
    {
        $this->setHeaders([
            'Cache-Control' => "max-age={$ttl}, public",
            'Expires' => gmdate('D, d M Y H:i:s', time() + $ttl) . ' GMT',
        ]);

        return $this;
    }

    /**
     * Disable caching
     *
     * @return Response Returns self for method chaining
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function withoutCaching(): Response
    {
        $this->setHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);

        return $this;
    }

    /**
     * Send the response to the client
     *
     * Outputs the response headers and content to the client.
     * This method should be called only once per request.
     *
     * @return void
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @uses serializeContent To convert content to string format
     */
    public function send(): void
    {
        // Set HTTP status code
        $statusMessage = self::STATUS_MESSAGES[$this->statusCode] ?? 'Unknown Status';
        header("HTTP/1.1 {$this->statusCode} {$statusMessage}");

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Send content
        echo $this->serializeContent();
    }

    /**
     * Get the response as a string without sending it
     *
     * Useful for testing or when you need to capture the response
     * content without sending it to the client.
     *
     * @return string The serialized response content
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getOutput(): string
    {
        return $this->serializeContent();
    }

    /**
     * Serialize content based on content type
     *
     * Converts the response content to the appropriate string format
     * based on the content type (JSON, XML, plain text).
     *
     * @return string Serialized content
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function serializeContent(): string
    {
        if ($this->content === null) {
            return '';
        }

        switch ($this->contentType) {
            case 'application/json':
            case 'application/json; charset=utf-8':
                return json_encode($this->content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            case 'application/xml':
                return $this->arrayToXml($this->content);

            case 'text/plain':
            case 'text/html':
                return (string) $this->content;

            default:
                // If content is array/object, try to JSON encode it
                if (is_array($this->content) || is_object($this->content)) {
                    return json_encode($this->content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                }
                return (string) $this->content;
        }
    }

    /**
     * Convert array data to XML format
     *
     * Simple XML conversion for basic data structures.
     * For complex XML requirements, consider using a dedicated XML library.
     *
     * @param mixed  $data        Data to convert to XML
     * @param string $rootElement Root XML element name
     *
     * @return string XML representation
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function arrayToXml(mixed $data, string $rootElement = 'response'): string
    {
        if (!is_array($data) && !is_object($data)) {
            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<{$rootElement}>" .
                   htmlspecialchars((string) $data) . "</{$rootElement}>";
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<{$rootElement}>\n";

        foreach ((array) $data as $key => $value) {
            $key = is_numeric($key) ? 'item' : $key;
            if (is_array($value) || is_object($value)) {
                $xml .= "  <{$key}>" . $this->arrayToXml($value, '') . "</{$key}>\n";
            } else {
                $xml .= "  <{$key}>" . htmlspecialchars((string) $value) . "</{$key}>\n";
            }
        }

        $xml .= "</{$rootElement}>";

        return $xml;
    }
}
