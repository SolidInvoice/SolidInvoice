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

use Symfony\Component\DependencyInjection\ContainerInterface,
	Symfony\Component\HttpFoundation\RedirectResponse;

use Doctrine\Common\Util\Inflector;

/**
 * Installer service
 */
class Installer
{
	const INSTALLER_ROUTE = '_installer';
	const INSTALLER_SUCCESS_ROUTE = '_installer_success';
	const INSTALLER_RESTART_ROUTE = '_installer_restart';

    /**
     * @var Container $container
     */
    protected $container;

    /**
     * Default available steps
     *
     * @param array $steps
     */
    protected $steps = array('license_agreement', 'system_check', 'database_config', 'system_information');

    /**
     * Object instance of current step
     *
     * @param Step $step
     */
    protected $step;

    /**
     * Constructer to initialize the installer
     *
     * @param ContainerInterface $container
     *
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);

        $session = $this->getSession('step');

        // If we don't have a step in the session yet (I.E first time we open installer), default to first step
        if (!$session) {
            $key = 0;
        } else {
            // otherwise search for current step
            $key = array_search($session, $this->steps);
        }

        $step = $this->getStep($key);

        $this->setStep($step);

    }

    /**
     * Initialized the current step, and set the session data
     *
     * @param string $step
     * @return void
     */
    public function setStep($step)
    {
    	$this->step($step);

    	$this->setSession('step', $step);
    }

    /**
     * Validte the current installation step to ensure paramaters are met
     *
     * @return RedirectResponse|false
     */
    public function validateStep($options)
    {
        $this->step->clear();

        // if step is valid, continue to next step
        if ($this->step->validate($options)) {
            // Process the current step (save configuration data, run database queries etc)
            $this->step->process($options);

            $session = $this->getSession('step');

            $key = array_search($session, $this->steps);

            $key++;

            try {
            	$step = $this->getStep($key);
            } catch (\OutOfRangeException $e)
            {
            	$route = $this->getContainer()->get('router')->generate(self::INSTALLER_SUCCESS_ROUTE);

            	return new RedirectResponse($route);
            }

            $this->setStep($step);

            // save all the request data in the session so we can use it later
            //$this->setSession($session, $options);

            return $this->getRedirectResponse();
        }

        return false;
    }

    /**
     * Restarts the installation process
     *
     * This method deletes all installation data
     * in the session, so the installation process
     * can be started from the beginning
     *
     * @return void
     */
    public function restart()
    {
    	$session = $this->getContainer()->get('session');

    	$keys = $session->all();

    	array_walk($keys, function($value, $key) use ($session){
    		$session->remove($key);
    	});
    }

    /**
     * Gets the route for the installer, and return a RedirectResponse
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function getRedirectResponse()
    {
    	$route = $this->getContainer()->get('router')->generate(self::INSTALLER_ROUTE);

    	return new RedirectResponse($route);
    }

    /**
     * Get the session data for specific step
     *
     * @return mixed
     * @param  string $key
     */
    public function getSession($key)
    {
        return unserialize($this->getContainer()->get('session')->get('installer.'.$key));
    }

    /**
     * Sets session data for specific key in installation process
     *
     * @return Installer
     */
    public function setSession($key, $value)
    {
        $session = $this->getContainer()->get('session');
        $session->set('installer.'.$key, serialize($value));

        return $this;
    }

    /**
     * Gets the service container
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets the instance of the container
     *
     * @param  ContainerInterface $container
     * @return Installer
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Returns an array of all available steps, formatted to human-readible name
     *
     * @return array
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Return the current active step
     *
     * @return string
     */
    public function active()
    {
        return $this->getSession('step');
    }

    /**
     * Creates an instance of the necessary step class
     *
     * @param  string $step
     * @return string
     */
    public function step($step_name = null)
    {
        $step = $this->checkName($step_name);

        $class = __NAMESPACE__.'\\Step\\'.$step;

        $this->step = new $class;

        $this->step->setContainer($this->getContainer());

        return $this->step;
    }

    /**
     * Converts a name to camelcase
     *
     * @param  string $name
     * @return mixed
     */
    private function checkName($name = '')
    {
        return Inflector::classify($name);
    }

    /**
     * Get the current installation step
     *
     * @return string
     */
    public function getStep($key = null)
    {
    	if(is_null($key))
    	{
    		// get necessary information for current step
    		$this->step->start();

    		return $this->step;
    	}

    	if(isset($this->steps[$key]))
    	{
    		return $this->steps[$key];
    	}

    	throw new \OutOfRangeException('Invalid step requested');
    }
}
