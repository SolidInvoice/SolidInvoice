<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Grid;

use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\DataGridBundle\AbstractGrid;
use CSBill\DataGridBundle\Action\ActionColumn;
use CSBill\DataGridBundle\Action\Collection;
use CSBill\DataGridBundle\Action\DeleteMassAction;
use CSBill\DataGridBundle\Action\MassAction;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Exception\InvalidTransitionException;
use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\InvoiceBundle\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class InvoiceRecurringGrid extends AbstractGrid
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var InvoiceManager
     */
    private $invoiceManager;

    /**
     * @var
     */
    private $session;

    /**
     * @param EntityManagerInterface $entityManager
     * @param InvoiceManager         $invoiceManager
     * @param SessionInterface       $session
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        InvoiceManager $invoiceManager,
        SessionInterface $session
    ) {
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->invoiceManager = $invoiceManager;
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
        $queryBuilder->where('i.recurring = 1');

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
        $archiveAction = new MassAction('Archive', function ($ids) {
            /** @var InvoiceRepository $invoiceRepository */
            $invoiceRepository = $this->entityManager->getRepository('CSBillInvoiceBundle:Invoice');

            /** @var Invoice[] $invoices */
            $invoices = $invoiceRepository->findBy(array('id' => $ids));

            /** @var FlashBag $flashBag */
            $flashBag = $this->session->getBag('flashes');

            $failed = 0;
            foreach ($invoices as $invoice) {
                try {
                    $this->invoiceManager->archive($invoice);
                } catch (InvalidTransitionException $e) {
                    $flashBag->add('warning', $e->getMessage());
                    ++$failed;
                }
            }

            if ($failed !== count($invoices)) {
                $flashBag->add('success', 'invoice.archive.success');
            }
        }, true);

        $archiveAction->setIcon('archive');
        $archiveAction->setClass('warning');

        return array(
            $archiveAction,
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

    /**
     * {@inheritdoc}
     */
    public function getRowActions(Collection $collection)
    {
        $viewAction = new ActionColumn();
        $viewAction->setIcon('eye')
            ->setTitle('invoice.action.view')
            ->setRoute('_invoices_view');

        $editAction = new ActionColumn();
        $editAction->setIcon('edit')
            ->setTitle('invoice.action.edit')
            ->setRoute('_invoices_edit');

        $collection->add($viewAction);
        $collection->add($editAction);
    }
}
