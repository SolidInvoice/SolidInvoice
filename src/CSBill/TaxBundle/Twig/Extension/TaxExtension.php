<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\TaxBundle\Twig\Extension;

use CSBill\TaxBundle\Repository\TaxRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class TaxExtension extends \Twig_Extension
{
    /**
     * @var TaxRepository
     */
    private $repository;

    /**
     * @param $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->repository = $doctrine->getManager()->getRepository('CSBillTaxBundle:Tax');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('taxRatesConfigured', [$this, 'taxRatesConfigured'])
        ];
    }
    
    /**
     * @return true
     */
    public function taxRatesConfigured()
    {
       return $this->repository->taxRatesConfigured();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'tax_extension';
    }
}