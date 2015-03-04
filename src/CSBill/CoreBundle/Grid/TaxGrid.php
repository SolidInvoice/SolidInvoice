<?php
/**
 * This file is part of the CSBill project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\CoreBundle\Grid;

use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\DataGridBundle\Action\ActionColumn;
use CSBill\DataGridBundle\Action\Collection;
use CSBill\DataGridBundle\Action\DeleteMassAction;
use CSBill\DataGridBundle\Grid\Filters;
use CSBill\DataGridBundle\GridInterface;
use Doctrine\ORM\QueryBuilder;

class TaxGrid implements GridInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return new Entity('CSBillCoreBundle:Tax');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(Filters $filters)
    {
        $filters->add(
            'inclusive',
            function (QueryBuilder $queryBuilder) {
                $aliases = $queryBuilder->getRootAliases();
                $alias = $aliases[0];

                $queryBuilder
                    ->andWhere($alias.'.type = :type')
                    ->setParameter('type', 'inclusive');
            }
        );

        $filters->add(
            'exclusive',
            function (QueryBuilder $queryBuilder) {
                $aliases = $queryBuilder->getRootAliases();
                $alias = $aliases[0];

                $queryBuilder
                    ->andWhere($alias.'.type = :type')
                    ->setParameter('type', 'exclusive');
            }
        );

        return $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function search(QueryBuilder $queryBuilder, $searchString)
    {
        $aliases = $queryBuilder->getRootAliases();

        $queryBuilder->andWhere($aliases[0] . '.name LIKE :search')
            ->setParameter('search', "%{$searchString}%");
    }

    /**
     * {@inheritdoc}
     */
    public function getRowActions(Collection $collection)
    {
        $editAction = new ActionColumn();
        $editAction->setIcon('edit')
            ->setTitle('Edit Tax Rate')
            ->setRoute('_edit_tax_rate')
        ;

        $deleteAction = new ActionColumn();
        $deleteAction->setIcon('times')
            ->setTitle('Delete Tax')
            ->setRoute('_delete_tax_rate')
            ->setConfirm('Are you sure you want to delete this tax method?')
            ->setAttributes(array('class' => 'delete-tax'))
            ->setClass('danger')
        ;

        $collection->add($editAction);
        $collection->add($deleteAction);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getMassActions()
    {
        return array(
            new DeleteMassAction()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'CSBillCoreBundle:tax:index.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable()
    {
        return true;
    }
}