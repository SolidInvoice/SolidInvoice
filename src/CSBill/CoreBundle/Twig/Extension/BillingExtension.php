<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle\Twig\Extension;

use Twig_Extension;
use Twig_Test_Function;
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
            $statusList = $this->doctrine->getRepository('CSBillQuoteBundle:Status')->findList();

            if (is_array($statusList) && count($statusList) > 0) {
                foreach ($statusList as $status) {
                    $tests[$status] = new Twig_Test_Function(function($a) use ($status) {
                        return strtolower($a->getStatus()->getName()) === strtolower($status);
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
