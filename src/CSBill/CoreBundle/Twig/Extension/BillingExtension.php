<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Twig\Extension;

use CSBill\CoreBundle\Util\ArrayUtil;
use Twig_Extension;
use Twig_Test_Function;
use Twig_SimpleTest;
use Doctrine\Common\Persistence\ManagerRegistry;
use CSBill\InstallBundle\Installer\Installer;

class BillingExtension extends Twig_Extension
{
    /**
     * @var ManagerRegistry $doctrine
     */
    protected $doctrine;

    /**
     * @var Installer $installer
     */
    protected $installer;

    /**
     * Sets the doctrine instance
     *
     * @param ManagerRegistry $doctrine
     */
    public function setDoctrine(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Sets the installer
     *
     * @param Installer $installer
     */
    public function setInstaller(Installer $installer)
    {
        $this->installer = $installer;
    }

    /**
     * Get status tests
     *
     * This checks is a quote/invoice has a specific status
     *
     * @return array
     */
    public function getTests()
    {
        $tests = array();

        if ($this->installer->isInstalled()) {
            // test if a quote/invoice is a specific status
            $statusList = array_unique(array_merge(
                ArrayUtil::column($this->doctrine->getRepository('CSBillQuoteBundle:Status')->getStatusList(), 'name'),
                ArrayUtil::column($this->doctrine->getRepository('CSBillInvoiceBundle:Status')->getStatusList(), 'name')
            ));

            if (is_array($statusList) && count($statusList) > 0) {
                foreach ($statusList as $status) {
                    $tests[] = new Twig_SimpleTest($status, function ($entity) use ($status) {
                        return strtolower($entity->getStatus()->getName()) === strtolower($status);
                    });
                }
            }
        }

        return $tests;
    }

    /**
     * {inhertitDoc}
     */
    public function getName()
    {
        return 'csbill_core.twig.billing';
    }
}
