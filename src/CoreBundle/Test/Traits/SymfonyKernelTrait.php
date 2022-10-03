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

use SolidInvoice\Kernel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @codeCoverageIgnore
 */
trait SymfonyKernelTrait
{
    /**
     * @var string
     */
    protected static $class = Kernel::class;

    /**
     * @var KernelInterface|null
     */
    protected static $kernel;

    /**
     * @var ContainerInterface|null
     */
    protected static $container;

    /**
     * @var bool
     */
    protected static $booted = false;

    /**
     * @var ContainerInterface|null
     */
    private static $kernelContainer;

    private function doTearDown(): void
    {
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

        static::$kernel = static::createKernel($options);
        static::$kernel->boot();
        static::$booted = true;

        self::$kernelContainer = static::$kernel->getContainer();
        // @phpstan-ignore-next-line
        static::$container = self::$kernelContainer->has('test.service_container') ? self::$kernelContainer->get('test.service_container') : self::$kernelContainer;

        return static::$kernel;
    }

    /**
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function createKernel(array $options = []): KernelInterface
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

        return new static::$class($env, (bool) $debug);
    }

    /**
     * Shuts the kernel down if it was used in the test - called by the tearDown method by default.
     */
    protected static function ensureKernelShutdown(): void
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
            static::$booted = false;
        }

        if (self::$kernelContainer instanceof ResetInterface) {
            self::$kernelContainer->reset();
        }

        static::$container = self::$kernelContainer = null;
    }
}
