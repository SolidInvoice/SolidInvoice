<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection;

interface ContainerInterface
{
}

namespace Symfony\Component\HttpKernel;

interface KernelInterface
{
}

namespace Symfony\Bundle\FrameworkBundle\Test;

use Symfony\Component\HttpKernel\KernelInterface;

class KernelTestCase
{
    /**
     * @var KernelInterface|null
     */
    protected static $kernel;

    /**
     * @var TestContainer
     */
    protected static $container;

    /**
     * @var bool
     */
    protected static $booted;
}
