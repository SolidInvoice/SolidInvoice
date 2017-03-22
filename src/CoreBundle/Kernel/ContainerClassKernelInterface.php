<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Kernel;

use Symfony\Component\HttpKernel\KernelInterface;

interface ContainerClassKernelInterface extends KernelInterface
{
    /**
     * Return the name of the cached container class.
     *
     * @return string
     */
    public function getContainerCacheClass(): string;

    /**
     * Sets the path to the config directory.
     *
     * @return string
     */
    public function getConfigDir(): string;
}
