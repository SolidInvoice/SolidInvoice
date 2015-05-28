<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Kernel;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Interface KernelInterface.
 */
interface ContainerClassKernelInterface extends KernelInterface
{
    /**
     * Return the name of the cached container class.
     *
     * @return string
     */
    public function getContainerCacheClass();
}
