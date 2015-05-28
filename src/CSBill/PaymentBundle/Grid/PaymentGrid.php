<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Grid;

use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\DataGridBundle\AbstractGrid;
use Doctrine\ORM\QueryBuilder;

class PaymentGrid extends AbstractGrid
{
    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return new Entity('CSBillPaymentBundle:Payment');
    }

    /**
     * {@inheritdoc}
     */
    public function search(QueryBuilder $queryBuilder, $searchString)
    {
        $aliases = $queryBuilder->getRootAliases();

        $queryBuilder
            ->orWhere($aliases[0].'.message LIKE :search')
            ->orWhere($aliases[0].'.totalAmount LIKE :search')
            ->orWhere($aliases[0].'.currencyCode LIKE :search')
            ->orWhere($aliases[0].'.status LIKE :search')
            ->orWhere('_client.name LIKE :search')
            ->orWhere('_method.name LIKE :search')
            ->setParameter('search', "%{$searchString}%");
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'CSBillPaymentBundle:Default:list.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable()
    {
        return true;
    }
}
