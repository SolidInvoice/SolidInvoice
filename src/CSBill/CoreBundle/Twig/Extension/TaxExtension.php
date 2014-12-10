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

use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class TaxExtension
 *
 * @package CSBill\CoreBundle\Twig\Extension
 */
class TaxExtension extends \Twig_Extension
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('taxConfigured', array($this, 'isTaxConfigured')),
        );
    }

    /**
     * @return bool
     */
    public function isTaxConfigured()
    {
        $taxRepository = $this->doctrine->getRepository('CSBillCoreBundle:Tax');

        return $taxRepository->getTotal() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'tax';
    }
}
