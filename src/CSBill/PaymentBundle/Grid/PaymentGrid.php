<?php
/**
 * This file is part of the CSBill project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
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