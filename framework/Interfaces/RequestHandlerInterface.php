<?php

declare(strict_types=1);

namespace Hexify\Interfaces;

use Hexify\Http\Request;
use Hexify\Http\Response;

/**
 * Contract defining request handling strategies for different API types
 *
 * This interface defines the contract for request handlers that process
 * different types of API requests (REST, GraphQL, etc.). Each handler
 * implements the specific logic for its API type while maintaining a
 * consistent interface.
 *
 * Implements the Strategy pattern to allow the framework to support
 * multiple API paradigms through pluggable request handlers. This
 * enables the same framework to serve REST APIs, GraphQL APIs, or
 * other custom API formats.
 *
 * The framework uses this interface to remain agnostic about the API
 * type, delegating the actual request processing to the appropriate
 * strategy implementation based on configuration.
 *
 * @package Hexify\Interfaces
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 */
interface RequestHandlerInterface
{
    /**
     * Handle an HTTP request and return a response
     *
     * Processes the given HTTP request according to the specific API
     * paradigm implemented by this handler. This method encapsulates
     * all the logic needed to parse the request, execute business logic,
     * and generate an appropriate response.
     *
     * Each implementation should handle:
     * - Request validation and parsing
     * - Routing and method resolution
     * - Authentication and authorization
     * - Business logic execution
     * - Response formatting and serialization
     * - Error handling and response codes
     *
     * @param Request $request The HTTP request to process
     *
     * @return Response The HTTP response to send to the client
     *
     * @throws \Throwable When request processing fails
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @see Request For the request object structure
     * @see Response For the response object structure
     */
    public function handle(Request $request): Response;

    /**
     * Check if this handler can process the given request
     *
     * Determines whether this handler is capable of processing the
     * given request based on the request characteristics (path, headers,
     * content type, etc.). This allows the framework to automatically
     * select the appropriate handler for each request.
     *
     * @param Request $request The HTTP request to examine
     *
     * @return boolean True if this handler can process the request
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function canHandle(Request $request): bool;

    /**
     * Get the priority of this handler for request matching
     *
     * Returns a numeric priority value used to determine handler
     * precedence when multiple handlers can process the same request.
     * Higher values indicate higher priority.
     *
     * This allows for flexible handler ordering and ensures that
     * more specific handlers take precedence over generic ones.
     *
     * @return integer Handler priority (higher = more priority)
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getPriority(): int;
}
