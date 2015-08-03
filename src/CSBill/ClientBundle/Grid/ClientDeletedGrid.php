<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Grid;

use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Repository\ClientRepository;
use CSBill\DataGridBundle\AbstractGrid;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class ClientDeletedGrid extends AbstractGrid
{
    /**
     * @var EntityManagerInterface
     */
    private $objectManager;

    /**
     * @param EntityManagerInterface $doctrine
     */
    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->objectManager = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        $this->objectManager->getFilters()->disable('softdeleteable');

        $source = new Entity('CSBillClientBundle:Client');

        /** @var ClientRepository $repo */
        $repo = $this->objectManager->getRepository('CSBillClientBundle:Client');

        $queryBuilder = $repo->createQueryBuilder('c');
        $queryBuilder->where('c.deletedAt is not null');

        $source->initQueryBuilder($queryBuilder);

        return $source;
    }

    /**
     * {@inheritdoc}
     */
    public function search(QueryBuilder $queryBuilder, $searchString)
    {
        $aliases = $queryBuilder->getRootAliases();

        $queryBuilder->andWhere($aliases[0].'.name LIKE :search')
            ->setParameter('search', "%{$searchString}%");
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'CSBillClientBundle:Default:index.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable()
    {
        return true;
    }
}
