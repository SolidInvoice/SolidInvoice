<?php

namespace CSBill\CoreBundle\Kernel;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Interface KernelInterface
 *
 * @package CSBill\CoreBundle\Kernel
 */
interface ContainerClassKernelInterface extends KernelInterface
{
    /**
     * Return the name of the cached container class
     *
     * @return string
     */
    public function getContainerCacheClass();
}
