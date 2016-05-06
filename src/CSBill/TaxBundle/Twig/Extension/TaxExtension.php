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
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
	$this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('taxRatesConfigured', [$this, 'taxRatesConfigured']),
        ];
    }

    /**
     * @return true
     */
    public function taxRatesConfigured()
    {
	return $this->registry->getRepository('CSBillTaxBundle:Tax')->taxRatesConfigured();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'tax_extension';
    }
}
