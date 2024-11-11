<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Test\Traits;

use LogicException;
use SolidInvoice\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\TestContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Service\ResetInterface;
use function assert;

/**
 * @codeCoverageIgnore
 */
trait SymfonyKernelTrait
{
    protected static ?KernelInterface $kernel = null;

    /**
     * @var ContainerInterface|null
     *
     * @deprecated use static::getContainer() instead
     */
    protected static $container;

    protected static bool $booted = false;

    protected function tearDown(): void
    {
        parent::tearDown();

        static::ensureKernelShutdown();
        static::$kernel = null;
        static::$booted = false;
    }

    /**
     * Boots the Kernel for this test.
     *
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function bootKernel(array $options = []): KernelInterface
    {
        static::ensureKernelShutdown();

        $kernel = static::createKernel($options);
        $kernel->boot();
        static::$kernel = $kernel;
        static::$booted = true;

        return static::$kernel;
    }

    /**
     * Provides a dedicated test container with access to both public and private
     * services. The container will not include private services that have been
     * inlined or removed. Private services will be removed when they are not
     * used by other services.
     *
     * Using this method is the best way to get a container from your test code.
     */
    protected static function getContainer(): TestContainer
    {
        if (! static::$booted) {
            static::bootKernel();
        }

        try {
            // @phpstan-ignore-next-line
            $container = static::$kernel->getContainer()->get('test.service_container');
            assert($container instanceof TestContainer);

            return $container;
        } catch (ServiceNotFoundException $e) {
            throw new LogicException('Could not find service "test.service_container". Try updating the "framework.test" config to "true".', 0, $e);
        }
    }

    /**
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function createKernel(array $options = []): Kernel
    {
        if (isset($options['environment'])) {
            $env = $options['environment'];
        } elseif (isset($_ENV['SOLIDINVOICE_ENV'])) {
            $env = $_ENV['SOLIDINVOICE_ENV'];
        } elseif (isset($_SERVER['SOLIDINVOICE_ENV'])) {
            $env = $_SERVER['SOLIDINVOICE_ENV'];
        } else {
            $env = 'test';
        }

        if (isset($options['debug'])) {
            $debug = $options['debug'];
        } elseif (isset($_ENV['SOLIDINVOICE_DEBUG'])) {
            $debug = $_ENV['SOLIDINVOICE_DEBUG'];
        } elseif (isset($_SERVER['SOLIDINVOICE_DEBUG'])) {
            $debug = $_SERVER['SOLIDINVOICE_DEBUG'];
        } else {
            $debug = true;
        }

        return new Kernel($env, (bool) $debug);
    }

    /**
     * Shuts the kernel down if it was used in the test - called by the tearDown method by default.
     */
    protected static function ensureKernelShutdown(): void
    {
        if (null !== static::$kernel) {
            static::$kernel->boot();
            $container = static::$kernel->getContainer();

            // Instantiate the service because Container::reset() only resets services that have been used
            $container->get('services_resetter');

            static::$kernel->shutdown();
            static::$booted = false;

            if ($container instanceof ResetInterface) {
                $container->reset();
            }
        }
    }
}
