<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
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
    public function getContainerCacheClass();

    /**
     * Sets the path to the config directory.
     */
    public function getConfigDir();
}
