<?php

declare(strict_types=1);

namespace Hexify\Http;

use Hexify\Interfaces\RequestHandlerInterface;
use Hexify\Http\Handlers\RestRequestHandler;
use Hexify\Http\Handlers\GraphQLRequestHandler;

/**
 * Factory for selecting appropriate request handlers based on request characteristics
 *
 * This factory implements the Factory Method pattern to automatically select
 * the most appropriate request handler (REST, GraphQL, etc.) based on the
 * incoming HTTP request characteristics such as path, headers, and content type.
 *
 * The factory evaluates each registered handler using their canHandle() method
 * and selects the one with the highest priority that can process the request.
 * This enables the framework to remain agnostic about API paradigms while
 * providing intelligent request routing.
 *
 * Implements the Chain of Responsibility pattern for handler selection,
 * allowing multiple handlers to be evaluated in priority order until
 * a suitable one is found.
 *
 * Features:
 * - Automatic handler detection based on request characteristics
 * - Priority-based handler selection for conflict resolution
 * - Extensible handler registration system
 * - Fallback mechanisms for unhandled requests
 * - Performance optimization through handler caching
 * - Support for custom handler implementations
 *
 * @package Hexify\Http
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 *
 * @see RequestHandlerInterface For the handler contract
 * @see RestRequestHandler For REST API handling
 * @see GraphQLRequestHandler For GraphQL API handling
 */
class RequestHandlerFactory
{
    /**
     * Registered request handlers
     *
     * @var array<RequestHandlerInterface> Array of registered handlers
     * @since 1.0.0
     */
    private array $handlers;

    /**
     * Handler selection cache for performance optimization
     *
     * @var array<string, RequestHandlerInterface> Cache of request → handler mappings
     * @since 1.0.0
     */
    private array $cache;

    /**
     * Create a new RequestHandlerFactory instance
     *
     * Initializes the factory with default handlers for common API types.
     * Additional handlers can be registered using the register() method.
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct()
    {
        $this->handlers = [];
        $this->cache = [];

        // Register default handlers
        $this->registerDefaultHandlers();
    }

    /**
     * Create and return the appropriate request handler for the given request
     *
     * Analyzes the request characteristics and selects the most appropriate
     * handler based on the canHandle() method and priority scores. Uses
     * caching to optimize repeated requests with similar characteristics.
     *
     * Handler Selection Process:
     * 1. Check cache for previously resolved handlers
     * 2. Evaluate all registered handlers using canHandle()
     * 3. Sort compatible handlers by priority (highest first)
     * 4. Return the highest priority handler
     * 5. Cache the result for performance optimization
     *
     * @param Request $request The HTTP request to analyze
     *
     * @return RequestHandlerInterface The selected request handler
     *
     * @throws \RuntimeException When no suitable handler is found
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @uses getCacheKey To generate cache keys for request matching
     * @uses selectBestHandler To choose the optimal handler
     */
    public function createHandler(Request $request): RequestHandlerInterface
    {
        $cacheKey = $this->getCacheKey($request);

        // Check cache for previously resolved handler
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $compatibleHandlers = [];

        // Find all handlers that can process this request
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($request)) {
                $compatibleHandlers[] = $handler;
            }
        }

        if (empty($compatibleHandlers)) {
            throw new \RuntimeException(
                'No suitable request handler found for request: ' .
                $request->getMethod() . ' ' . $request->getPath()
            );
        }

        // Select the best handler based on priority
        $selectedHandler = $this->selectBestHandler($compatibleHandlers);

        // Cache the result for performance
        $this->cache[$cacheKey] = $selectedHandler;

        return $selectedHandler;
    }

    /**
     * Register a new request handler
     *
     * Adds a custom request handler to the factory. Handlers are evaluated
     * in the order they were registered, but selection is ultimately
     * determined by their priority scores.
     *
     * @param RequestHandlerInterface $handler The handler to register
     *
     * @return RequestHandlerFactory Returns self for method chaining
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function register(RequestHandlerInterface $handler): RequestHandlerFactory
    {
        $this->handlers[] = $handler;

        // Clear cache when handlers change
        $this->cache = [];

        return $this;
    }

    /**
     * Get all registered handlers
     *
     * @return array<RequestHandlerInterface> Array of registered handlers
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Clear the handler selection cache
     *
     * Useful for testing or when handler behavior changes during runtime.
     *
     * @return RequestHandlerFactory Returns self for method chaining
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function clearCache(): RequestHandlerFactory
    {
        $this->cache = [];
        return $this;
    }

    /**
     * Register default request handlers
     *
     * Sets up the standard handlers for common API types. This method
     * is called during factory initialization to provide out-of-the-box
     * support for REST and GraphQL APIs.
     *
     * @return void
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function registerDefaultHandlers(): void
    {
        // Register GraphQL handler (higher priority for specific requests)
        $this->register(new GraphQLRequestHandler());

        // Register REST handler (lower priority, handles most requests)
        $this->register(new RestRequestHandler());
    }

    /**
     * Generate cache key for request handler selection
     *
     * Creates a unique cache key based on request characteristics that
     * influence handler selection. This allows caching of handler
     * decisions for performance optimization.
     *
     * @param Request $request The HTTP request
     *
     * @return string Cache key for handler selection
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function getCacheKey(Request $request): string
    {
        $characteristics = [
            'method' => $request->getMethod(),
            'path' => $request->getPath(),
            'content_type' => $request->getContentType(),
            'accept' => $request->getHeader('accept'),
        ];

        // Check for GraphQL indicators in request body
        $body = $request->getBodyData();
        if (isset($body['query'])) {
            $characteristics['has_graphql_query'] = true;
        }

        return md5(serialize($characteristics));
    }

    /**
     * Select the best handler from compatible handlers
     *
     * Chooses the optimal handler based on priority scores when multiple
     * handlers can process the same request. Higher priority handlers
     * are preferred to ensure more specific handlers take precedence.
     *
     * @param array<RequestHandlerInterface> $handlers Compatible handlers
     *
     * @return RequestHandlerInterface The selected handler
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function selectBestHandler(array $handlers): RequestHandlerInterface
    {
        // Sort handlers by priority (highest first)
        usort($handlers, function (RequestHandlerInterface $a, RequestHandlerInterface $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        // Return the highest priority handler
        return $handlers[0];
    }
}
