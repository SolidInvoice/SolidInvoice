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

use Doctrine\DBAL\DBALException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CSBill\InstallBundle\Exception\InvalidStepException;
use CSBill\InstallBundle\Installer\Step\DatabaseConfig;
use CSBill\InstallBundle\Installer\Step\LicenseAgreement;
use CSBill\InstallBundle\Installer\Step\SystemCheck;
use CSBill\InstallBundle\Installer\Step\SystemInformation;

/**
 * Installer service
 */
class Installer
{
    const INSTALLER_ROUTE = '_installer';
    const INSTALLER_SUCCESS_ROUTE = '_installer_success';
    const INSTALLER_RESTART_ROUTE = '_installer_restart';

    const SESSION_KEY = 'session.';

    /**
     * The current step in the installation process
     *
     * @var array
     */
    public $currentStep = array();

    /**
     * The index of the current active step
     *
     * @var array(type => StepInterface)
     */
    public $currentStepIndex = 0;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Default available steps
     *
     * @var array
     */
    protected $steps = array();

    /**
     * Flag to set if installer is on the last step
     *
     * @var bool
     */
    protected $isFinal = false;

    /**
     * Constructer to initialize the installer
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);

        $this->steps = $this->getSteps();
    }

    /**
     * Returns an array of all available steps, formatted to human-readible name
     *
     * @return array
     */
    public function getSteps()
    {
        return array(
            array(
                'label' => 'license_agreement',
                'type'  => new LicenseAgreement,
            ),
            array(
                'label' => 'system_check',
                'type'  => new SystemCheck,
            ),
            array(
                'label' => 'database_configuration',
                'type'  => new DatabaseConfig,
            ),
            array(
                'label' => 'system_information',
                'type'  => new SystemInformation,
            )
        );
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
        $this->getContainer()->get('session')->clear();
    }

    /**
     * Get the session data for specific step
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getSession($key, $default = null)
    {
        $session = $this->getContainer()->get('session');

        return $session->get(self::SESSION_KEY . $key, $default);
    }

    /**
     * Sets session data for specific key in installation process
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setSession($key, $value)
    {
        $session = $this->getContainer()->get('session');

        $session->set(self::SESSION_KEY . $key, $value);

        return $this;
    }

    /**
     * Gets the service container
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets the instance of the container
     *
     * @param ContainerInterface $container
     *                                      @return $this
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Return the current active step
     *
     * @throws \Exception
     *
     * @return string
     */
    public function advanceStep()
    {
        $currentStep = ((int) $this->getSession('current_step', 0)) + 1;

        if (!array_key_exists($currentStep, $this->steps)) {
            throw new \Exception('Invalid step requested');
        }

        $this->setSession('current_step', $currentStep);
        $this->setSession(sprintf('step_%d_complete', $currentStep - 1), true);
    }

    /**
     * Get the current installation step
     *
     * @throws InvalidStepException
     * @throws \OutOfRangeException
     * @throws \Exception
     *
     * @return StepInterface|StepFormInterface
     */
    public function getCurrentStep()
    {
        $sessionKey = (int) $this->getSession('current_step', 0);

        if (array_key_exists($sessionKey, $this->steps)) {
            $currentStep = $this->steps[$sessionKey];

            if (
                !is_array($currentStep) &&
                !array_key_exists('label', $currentStep) &&
                !array_key_exists('type', $currentStep)
            ) {
                $keys = implode(', ', array('label', 'type'));

                throw new \Exception(
                    sprintf('The data for the current step is invalid. Expected an array with keys (%s)', $keys)
                );
            }

            $this->currentStep = $currentStep;
            $this->currentStepIndex = $sessionKey;

            if (!$this->currentStep['type'] instanceof StepInterface) {
                throw new InvalidStepException($currentStep);
            }

            $this->currentStep['type']->setContainer($this->container);
            $this->currentStep['type']->init();

            $stepKeys = array_keys($this->steps);
            $lastKey = array_pop($stepKeys);

            if ($sessionKey === $lastKey) {
                $this->isFinal = true;
            }

            return $this->currentStep['type'];
        }

        throw new \OutOfRangeException('An invalid step was requested during installation process');
    }

    /**
     * @return bool
     */
    public function previousStepComplete()
    {
        $currentStep = (int) $this->getSession('current_step', 0);

        if (0 === $currentStep) {
            // return true if we are on the first step
            return true;
        }

        return $this->isStepComplete($currentStep - 1);
    }

    /**
     * @param  int  $index
     * @return bool
     */
    public function isStepComplete($index)
    {
        return (bool) $this->getSession(sprintf('step_%d_complete', $index));
    }

    /**
     * Are we currently on the final step
     *
     * @return bool
     */
    public function isFinal()
    {
        return $this->isFinal;
    }

    /**
     * Checks if the application is currently installed
     *
     * @return bool
     */
    public function isInstalled()
    {
        // check if we can connect to the database
        try {
            $this->container->get('database_connection')->connect();
        } catch (DBALException $e) {
            // if we can't connect to the database, assume the application is not installed
            // @TODO we should cater for cases when the database is down
            return false;
        }

        /*
            @TODO: check (settings table|composer.lock file) for current installed version.
            If version can't be found, run installer
        */
        // if version is older than available version, go to upgrade page (unless automatic update is activiated)
        // (Should we automatically take user to upgrade page, or just notify that a new version is available?)

        /**
         * Temporary Implemation
         */

        // check if the users table exists. If not, go to installer
        $repository = $this->container->get('doctrine.orm.entity_manager')->getRepository('CSBillUserBundle:User');

        try {
            $users = $repository->createQueryBuilder('u')->setMaxResults(1)->getQuery()->execute();

            if (count($users) === 0) {
                throw new \RuntimeException('The users table does not exist');
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

}
