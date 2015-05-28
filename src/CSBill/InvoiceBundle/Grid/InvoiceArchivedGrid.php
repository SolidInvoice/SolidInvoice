<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Grid;

use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\DataGridBundle\AbstractGrid;
use CSBill\DataGridBundle\Action\DeleteMassAction;
use CSBill\InvoiceBundle\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class InvoiceArchivedGrid extends AbstractGrid
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var
     */
    private $session;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SessionInterface       $session
     */
    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session)
    {
        $this->entityManager = $entityManager;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        $this->entityManager->getFilters()->disable('archivable');

        $source = new Entity('CSBillInvoiceBundle:Invoice');

        /** @var InvoiceRepository $repo */
        $repo = $this->entityManager->getRepository('CSBillInvoiceBundle:Invoice');

        $queryBuilder = $repo->createQueryBuilder('i');
        $queryBuilder->where('i.archived is not null');

        $source->initQueryBuilder($queryBuilder);

        return $source;
    }

    /**
     * {@inheritdoc}
     */
    public function search(QueryBuilder $queryBuilder, $searchString)
    {
        $alias = $queryBuilder->getRootAliases();
        $queryBuilder
            ->orWhere('_client.name LIKE :search')
            ->orWhere($alias[0].'.status LIKE :search')
            ->orWhere($alias[0].'.total LIKE :search')
            ->setParameter('search', "%{$searchString}%");
    }

    /**
     * {@inheritdoc}
     */
    public function getMassActions()
    {
        return array(
            new DeleteMassAction(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'CSBillInvoiceBundle:Default:index.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable()
    {
        return true;
    }
}
