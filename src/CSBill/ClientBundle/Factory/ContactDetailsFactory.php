<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Factory;

use CSBill\InstallBundle\Installer\Installer;
use Doctrine\Bundle\DoctrineBundle\Registry;

class ContactDetailsFactory
{
    /**
     * @var \CSBill\InstallBundle\Installer\Installer
     */
    protected $intaller;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager|object
     */
    protected $doctrine;

    /**
     * @param Installer $installer
     * @param Registry  $doctrine
     */
    public function __construct(Installer $installer, Registry $doctrine)
    {
        $this->intaller = $installer;
        $this->doctrine = $doctrine->getManager();
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        if ($this->intaller->isInstalled()) {
            $repository = $this->doctrine->getRepository('CSBillClientBundle:ContactType');

            return $repository->findAll();
        }

        return array();
    }
}
