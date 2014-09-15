<?php
/**
 * This file is part of the MiWay Business Insurance project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
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
            new \Twig_SimpleFunction('taxConfigured', array($this, 'isTaxConfigured'))
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