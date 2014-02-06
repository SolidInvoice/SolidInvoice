<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Installer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractStep implements StepInterface
{
    /**
     * Contains an instance of the service container
     *
     * @var ContainerInterface $container
     */
    protected $container;

    /**
     * Set the instance of the service container
     *
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;

        return $this;
    }

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
