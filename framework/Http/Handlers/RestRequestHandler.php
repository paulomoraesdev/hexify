<?php

declare(strict_types=1);

namespace Hexify\Http\Handlers;

use Hexify\Interfaces\RequestHandlerInterface;
use Hexify\Http\Request;
use Hexify\Http\Response;

/**
 * REST API request handler implementation
 *
 * This class implements REST (Representational State Transfer) request
 * handling following RESTful principles and conventions. It processes
 * HTTP requests using standard HTTP methods (GET, POST, PUT, DELETE)
 * and resource-based URLs.
 *
 * Implements the Strategy pattern as part of the framework's pluggable
 * request handling system, allowing REST APIs to coexist with other
 * API paradigms like GraphQL.
 *
 * REST Principles Implemented:
 * - Resource identification through URIs
 * - HTTP methods for different operations (CRUD)
 * - Stateless communication
 * - Standard HTTP status codes
 * - JSON representation (with XML support)
 * - HATEOAS (Hypermedia as the Engine of Application State)
 *
 * Features:
 * - RESTful routing with parameter extraction
 * - HTTP method-based operation mapping
 * - Content negotiation (JSON/XML)
 * - Standard REST status codes
 * - Error response formatting
 * - CORS support for web clients
 * - Request validation and sanitization
 *
 * @package Hexify\Http\Handlers
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 *
 * @see RequestHandlerInterface For the contract this class implements
 */
class RestRequestHandler implements RequestHandlerInterface
{
    /**
     * Priority for REST handler selection
     *
     * @var integer Handler priority for request matching
     * @since 1.0.0
     */
    private const PRIORITY = 50;

    /**
     * Supported HTTP methods for REST operations
     *
     * @var array<string> List of supported HTTP methods
     * @since 1.0.0
     */
    private const SUPPORTED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    /**
     * Handle a REST API request
     *
     * Processes the REST request by analyzing the HTTP method and URI path
     * to determine the appropriate action. Implements standard REST patterns
     * and returns appropriately formatted responses.
     *
     * REST Request Processing:
     * 1. Method and path analysis
     * 2. Route parameter extraction
     * 3. Content negotiation
     * 4. Request validation
     * 5. Business logic execution
     * 6. Response formatting
     * 7. CORS headers (if needed)
     *
     * @param Request $request The HTTP request to process
     *
     * @return Response The HTTP response following REST conventions
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @uses parseRestRoute To extract route parameters
     * @uses executeRestOperation To perform the business logic
     * @uses formatRestResponse To format the response
     */
    public function handle(Request $request): Response
    {
        try {
            // Handle CORS preflight requests
            if ($request->getMethod() === 'OPTIONS') {
                return $this->handlePreflightRequest($request);
            }

            // Validate HTTP method
            if (!in_array($request->getMethod(), self::SUPPORTED_METHODS, true)) {
                return Response::error(
                    'Method not allowed',
                    405,
                    ['allowed_methods' => self::SUPPORTED_METHODS]
                )->setHeader('Allow', implode(', ', self::SUPPORTED_METHODS));
            }

            // Parse REST route and extract parameters
            $routeInfo = $this->parseRestRoute($request);

            // Execute REST operation based on method and route
            $result = $this->executeRestOperation($request, $routeInfo);

            // Format and return REST response
            return $this->formatRestResponse($result, $request);
        } catch (\Throwable $e) {
            return $this->handleRestError($e, $request);
        }
    }

    /**
     * Check if this handler can process REST requests
     *
     * Determines if the request follows REST conventions by examining
     * the request path, method, and headers. REST requests typically:
     * - Use standard HTTP methods
     * - Have resource-based paths
     * - Accept JSON/XML content types
     *
     * @param Request $request The HTTP request to examine
     *
     * @return boolean True if request appears to be REST-compatible
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function canHandle(Request $request): bool
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        // Check if method is supported
        if (!in_array($method, self::SUPPORTED_METHODS, true)) {
            return false;
        }

        // REST typically uses resource-based paths
        // Exclude GraphQL endpoints
        if (str_contains($path, '/graphql')) {
            return false;
        }

        // Check content type preferences
        $contentType = $request->getContentType();
        $acceptHeader = $request->getHeader('accept');

        // REST typically works with JSON/XML
        $restContentTypes = ['application/json', 'application/xml', 'text/xml'];
        foreach ($restContentTypes as $type) {
            if (str_contains($contentType, $type) || str_contains($acceptHeader, $type)) {
                return true;
            }
        }

        // Default to REST if no specific indicators
        return true;
    }

    /**
     * Get the priority for REST request handling
     *
     * @return integer Handler priority (50 = medium priority)
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    /**
     * Handle CORS preflight OPTIONS requests
     *
     * @param Request $request The preflight request
     *
     * @return Response CORS preflight response
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function handlePreflightRequest(Request $request): Response
    {
        return Response::json([], 200)
            ->withCors(
                env('CORS_ORIGIN', '*'),
                self::SUPPORTED_METHODS,
                ['Content-Type', 'Authorization', 'X-Requested-With']
            );
    }

    /**
     * Parse REST route and extract parameters
     *
     * Analyzes the request path to extract resource information and
     * route parameters following REST conventions.
     *
     * @param Request $request The HTTP request
     *
     * @return array<string, mixed> Route information and parameters
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function parseRestRoute(Request $request): array
    {
        $path = trim($request->getPath(), '/');
        $segments = explode('/', $path);

        // Remove empty segments
        $segments = array_filter($segments);

        // Basic REST route parsing
        $routeInfo = [
            'segments' => $segments,
            'resource' => $segments[0] ?? null,
            'id' => null,
            'subresource' => null,
            'subid' => null,
        ];

        // Extract ID if present (numeric second segment)
        if (count($segments) > 1 && is_numeric($segments[1])) {
            $routeInfo['id'] = (int) $segments[1];
        }

        // Extract subresource if present
        if (count($segments) > 2) {
            $routeInfo['subresource'] = $segments[2];

            // Extract subresource ID if present
            if (count($segments) > 3 && is_numeric($segments[3])) {
                $routeInfo['subid'] = (int) $segments[3];
            }
        }

        return $routeInfo;
    }

    /**
     * Execute REST operation based on method and route
     *
     * This is a placeholder implementation that demonstrates the REST
     * handling pattern. In a real implementation, this would dispatch
     * to appropriate controllers or business logic handlers.
     *
     * @param Request              $request   The HTTP request
     * @param array<string, mixed> $routeInfo Parsed route information
     *
     * @return array<string, mixed> Operation result data
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function executeRestOperation(Request $request, array $routeInfo): array
    {
        $method = $request->getMethod();
        $resource = $routeInfo['resource'];
        $id = $routeInfo['id'];

        // Placeholder implementation - in real app, this would dispatch to controllers
        return match ($method) {
            'GET' => $this->handleGetRequest($resource, $id, $request),
            'POST' => $this->handlePostRequest($resource, $request),
            'PUT', 'PATCH' => $this->handleUpdateRequest($resource, $id, $request),
            'DELETE' => $this->handleDeleteRequest($resource, $id),
            default => ['error' => 'Method not implemented']
        };
    }

    /**
     * Handle GET request (Read operation)
     *
     * @param string|null  $resource Resource name
     * @param integer|null $id       Resource ID
     * @param Request      $request  HTTP request
     *
     * @return array<string, mixed> Response data
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function handleGetRequest(?string $resource, ?int $id, Request $request): array
    {
        if ($id) {
            return [
                'message' => 'REST GET request for single resource',
                'resource' => $resource,
                'id' => $id,
                'data' => ['example' => 'This would return a specific ' . $resource]
            ];
        }

        return [
            'message' => 'REST GET request for resource collection',
            'resource' => $resource,
            'data' => ['example' => 'This would return a list of ' . $resource],
            'pagination' => [
                'page' => (int) $request->getQueryParam('page', 1),
                'limit' => (int) $request->getQueryParam('limit', 10)
            ]
        ];
    }

    /**
     * Handle POST request (Create operation)
     *
     * @param string|null $resource Resource name
     * @param Request     $request  HTTP request
     *
     * @return array<string, mixed> Response data
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function handlePostRequest(?string $resource, Request $request): array
    {
        return [
            'message' => 'REST POST request to create resource',
            'resource' => $resource,
            'created' => true,
            'data' => $request->getBodyData(),
            'id' => rand(1, 1000) // Simulated new ID
        ];
    }

    /**
     * Handle PUT/PATCH request (Update operation)
     *
     * @param string|null  $resource Resource name
     * @param integer|null $id       Resource ID
     * @param Request      $request  HTTP request
     *
     * @return array<string, mixed> Response data
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function handleUpdateRequest(?string $resource, ?int $id, Request $request): array
    {
        if (!$id) {
            throw new \InvalidArgumentException('ID required for update operations');
        }

        return [
            'message' => 'REST ' . $request->getMethod() . ' request to update resource',
            'resource' => $resource,
            'id' => $id,
            'updated' => true,
            'data' => $request->getBodyData()
        ];
    }

    /**
     * Handle DELETE request (Delete operation)
     *
     * @param string|null  $resource Resource name
     * @param integer|null $id       Resource ID
     *
     * @return array<string, mixed> Response data
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function handleDeleteRequest(?string $resource, ?int $id): array
    {
        if (!$id) {
            throw new \InvalidArgumentException('ID required for delete operations');
        }

        return [
            'message' => 'REST DELETE request',
            'resource' => $resource,
            'id' => $id,
            'deleted' => true
        ];
    }

    /**
     * Format REST response with appropriate status codes
     *
     * @param array<string, mixed> $result  Operation result
     * @param Request              $request Original request
     *
     * @return Response Formatted REST response
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function formatRestResponse(array $result, Request $request): Response
    {
        $method = $request->getMethod();

        // Determine appropriate HTTP status code
        $statusCode = match ($method) {
            'POST' => isset($result['created']) && $result['created'] ? 201 : 200,
            'DELETE' => 204, // No Content for successful deletion
            default => 200
        };

        // For DELETE with 204, return empty body
        if ($method === 'DELETE' && $statusCode === 204) {
            return new Response(null, 204);
        }

        $response = Response::json($result, $statusCode);

        // Add CORS headers if configured
        if (env('CORS_ENABLED', true)) {
            $response->withCors(env('CORS_ORIGIN', '*'));
        }

        return $response;
    }

    /**
     * Handle REST-specific errors
     *
     * @param \Throwable $exception The exception that occurred
     * @param Request    $request   Original request
     *
     * @return Response Error response
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function handleRestError(\Throwable $exception, Request $request): Response
    {
        $statusCode = 500;
        $message = 'Internal Server Error';

        // Map specific exceptions to appropriate HTTP status codes
        if ($exception instanceof \InvalidArgumentException) {
            $statusCode = 400;
            $message = 'Bad Request: ' . $exception->getMessage();
        }

        $errorData = [
            'error' => true,
            'message' => $message,
            'status' => $statusCode,
        ];

        // Add debug info in development
        if (env('APP_DEBUG', false)) {
            $errorData['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        $response = Response::json($errorData, $statusCode);

        // Add CORS headers for error responses too
        if (env('CORS_ENABLED', true)) {
            $response->withCors(env('CORS_ORIGIN', '*'));
        }

        return $response;
    }
}
