<?php

declare(strict_types=1);

namespace Hexify\Interfaces;

use Hexify\Foundation\Application;

/**
 * Contract defining bootstrap operations for application initialization
 *
 * This interface defines the contract for bootstrap classes that handle
 * different aspects of application initialization. Each bootstrapper is
 * responsible for a single aspect of the startup process, following the
 * Single Responsibility Principle.
 *
 * Implements the Chain of Responsibility pattern where each bootstrapper
 * can be executed in sequence during application startup, allowing for
 * modular and extensible initialization processes.
 *
 * @package Hexify\Interfaces
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 */
interface BootstrapperInterface
{
    /**
     * Bootstrap the application component
     *
     * Performs the specific bootstrap operation for this component.
     * Each bootstrapper should focus on a single responsibility
     * (loading environment, configuration, services, etc.).
     *
     * The application instance is passed to allow access to framework
     * services and configuration during the bootstrap process.
     *
     * @param Application $app The application instance being bootstrapped
     *
     * @return void
     *
     * @throws \Hexify\Exceptions\BootstrapException When bootstrap operation fails
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @see Application For the application instance structure
     */
    public function bootstrap(Application $app): void;
}
