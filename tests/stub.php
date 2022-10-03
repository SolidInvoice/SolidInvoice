<?php

namespace Symfony\Component\DependencyInjection;

interface ContainerInterface
{
}

namespace Symfony\Component\HttpKernel;

interface KernelInterface
{
}

namespace Symfony\Bundle\FrameworkBundle\Test;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelTestCase
{
    /**
     * @var KernelInterface|null
     */
    protected static $kernel;

    /**
     * @var ContainerInterface|null
     */
    protected static $container;

}
