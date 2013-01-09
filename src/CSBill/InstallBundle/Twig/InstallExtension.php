<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Twig;

use Twig_Extension,
    Twig_Test_Method;

use Doctrine\Common\Util\Inflector,
    Doctrine\Common\Util\ClassUtils;

use CSBill\InstallBundle\Installer\StepInterface,
    CSBill\InstallBundle\Installer\Installer;

use Symfony\Component\Security\Core\Util\StringUtils;

class InstallExtension extends Twig_Extension
{
    /**
     * Contains an instance of the installer service
     *
     * @var Installer $installer
     */
    protected $installer;

    /**
     * Sets an instance of the installer service
     *
     * @param Installer $installer
     */
    public function setInstaller(Installer $installer)
    {
        $this->installer = $installer;
    }

    /**
     * (non-phpdoc)
     *
     * @return array
     */
    public function getTests()
    {
        return array('first_install_step' => new Twig_Test_Method($this, 'isFirstInstallStep'));
    }

    /**
     * Checks if the current installation step is the first step
     *
     * @param  StepInterface $step
     * @return bool
     */
    public function isFirstInstallStep(StepInterface $step)
    {
        $class_name = ClassUtils::newReflectionObject($step)->getShortName();
        $step_name = Inflector::tableize($class_name);

        $first_step = $this->installer->getStep(0);

        return StringUtils::equals($step_name, $first_step);
    }

    /**
     * (non-phpdoc)
     *
     * @return string
     */
    public function getName()
    {
        return 'csbill.twig.install_extension';
    }
}
