<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Installer;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractStep extends ContainerAware implements StepInterface
{
    /**
     * Gets a service from the service container
     *
     * @param  string $service
     * @return object
     */
    public function get($service = '')
    {
        return $this->container->get($service);
    }

    /**
     * {@inheritDoc}
     */
    public function init()
    {
    }

    /**
     * {@inheritDoc}
     */
    abstract public function handleRequest(Request $request);

    /**
     * {@inheritDoc}
     */
    abstract public function isValid();

    /**
     * {@inheritDoc}
     */
    abstract public function process();
}
