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

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Abstract class to implement ArrayAccess and set default functionality
 */
abstract class Step implements StepInterface, ContainerAwareInterface, \ArrayAccess
{
    /**
     * Contains all errors for current step
     *
     * @var array $errors
     */
    protected $errors = array();

    /**
     * Contains an instance of the service container
     *
     * @var ContainerInterface $container
     *
     * @DI\Inject("service_container")
     */
    protected $container;

    /**
     * Set the instance of the service container
     *
     * @param  ContainerInterface $container
     * @return Step
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
     * Adds an error to the errors array
     *
     * @param  string $error
     * @return Step
     */
    public function addError($error)
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * Returns all the errors for the current installation step
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Check if the property exists for the current offset when using \ArrayAccess
     *
     * @param  mixed   $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }

    /**
     * Gets a property when using \ArrayAccess
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    /**
     * Sets the value for a property when using \ArrayAccess
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    /**
     * Sets the value for a property to null when using \ArrayAccess
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->{$offset} = null;
    }

    /**
     * Clears the the current step
     *
     * @return void
     */
    public function clear()
    {
        $this->errors = array();
    }
}
