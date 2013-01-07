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

use CSBill\InstallBundle\Installer\StepInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\DependencyInjection\Container;

/**
 * Abstract class to implement ArrayAccess and set default functionality
 */
abstract class Step implements StepInterface, \ArrayAccess
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
     * @var Container $container
     *
     * @DI\Inject("service_container")
     */
    protected $container;

    /**
     * Set the instance of the service container
     *
     * @param  Container $container
     * @return Step
     */
    public function setContainer(Container $container)
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

    /**
     * @param array $request
     */
    abstract public function validate(array $request);

    /**
     * @param array $request
     */
	abstract public function process(array $request);

	/**
	 * (non-phpdoc)
	 */
	abstract public function start();
}
