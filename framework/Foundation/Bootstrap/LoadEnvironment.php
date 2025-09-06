<?php

declare(strict_types=1);

namespace Hexify\Foundation\Bootstrap;

use Hexify\Interfaces\BootstrapperInterface;
use Hexify\Foundation\Application;
use Hexify\Environment\DotEnvReader;
use Hexify\Exceptions\BootstrapException;
use Hexify\Exceptions\EnvLoadException;

/**
 * Environment loading bootstrap class
 *
 * This bootstrapper is responsible for loading environment variables
 * from .env files during application startup. It ensures that all
 * environment configuration is available before other bootstrap
 * steps that might depend on environment values.
 *
 * Implements the Chain of Responsibility pattern as part of the
 * application bootstrap sequence. This step typically runs first
 * in the bootstrap chain to establish the application environment.
 *
 * @package Hexify\Foundation\Bootstrap
 * @author Paulo Moraes <accounts@paulomoraes.dev>
 * @since 1.0.0
 * @version 1.0.0
 *
 * @see BootstrapperInterface For the contract this class implements
 * @uses DotEnvReader To load environment variables from .env file
 */
class LoadEnvironment implements BootstrapperInterface
{
    /**
     * Bootstrap environment loading
     *
     * Loads environment variables from the .env file using the DotEnvReader.
     * This makes environment configuration available to all subsequent
     * bootstrap steps and the application runtime.
     *
     * If environment loading fails, wraps the underlying exception in a
     * BootstrapException with context about the failed bootstrap step.
     *
     * @param Application $app The application instance being bootstrapped
     *
     * @return void
     *
     * @throws BootstrapException When environment loading fails
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     * @uses DotEnvReader::getInstance() To get the environment reader
     * @uses DotEnvReader::load() To load environment variables
     */
    public function bootstrap(Application $app): void
    {
        try {
            $envReader = DotEnvReader::getInstance();
            $envReader->load();
        } catch (EnvLoadException $e) {
            throw new BootstrapException(
                "Failed to load environment configuration: {$e->getMessage()}",
                0,
                $e,
                'LoadEnvironment'
            );
        }
    }
}
