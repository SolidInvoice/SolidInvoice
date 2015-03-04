<?php
/**
 * This file is part of the CSBill project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\QuoteBundle\Grid;

use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\DataGridBundle\Action\ActionColumn;
use CSBill\DataGridBundle\Action\Collection;
use CSBill\DataGridBundle\Action\DeleteMassAction;
use CSBill\DataGridBundle\Action\MassAction;
use CSBill\DataGridBundle\Grid\Filters;
use CSBill\DataGridBundle\GridInterface;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Model\Graph;
use CSBill\QuoteBundle\Repository\QuoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Finite\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class QuoteGrid implements GridInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var FactoryInterface
     */
    private $finite;

    /**
     * @param EntityManagerInterface $entityManager
     * @param FactoryInterface       $finite
     * @param SessionInterface       $session
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        FactoryInterface $finite,
        SessionInterface $session
    ) {
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->finite = $finite;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return new Entity('CSBillQuoteBundle:Quote');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(Filters $filters)
    {
        $callback = function ($status) {
            return function (QueryBuilder $queryBuilder) use ($status) {
                $alias = $queryBuilder->getRootAliases();

                $queryBuilder->andWhere($alias[0].'.status = :status')
                    ->setParameter('status', $status);
            };
        };

        $statuses = array(
            Graph::STATUS_CANCELLED,
            Graph::STATUS_DRAFT,
            Graph::STATUS_PENDING,
            Graph::STATUS_ACCEPTED,
            Graph::STATUS_DECLINED,
        );

        foreach ($statuses as $status) {
            $filters->add(
                $status,
                $callback($status)
            );
        }

        return $filters;
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
    public function getRowActions(Collection $collection)
    {
        $viewAction = new ActionColumn();
        $viewAction->setIcon('eye')
            ->setTitle('quote.grid.action.view')
            ->setRoute('_quotes_view')
        ;

        $editAction = new ActionColumn();
        $editAction->setIcon('edit')
            ->setTitle('quote.grid.action.edit')
            ->setRoute('_quotes_edit')
        ;

        $collection->add($viewAction);
        $collection->add($editAction);
    }

    /**
     * {@inheritdoc}
     */
    public function getMassActions()
    {
        $archiveAction = new MassAction('Archive', function($ids) {
            /** @var QuoteRepository $quoteRepository */
            $quoteRepository = $this->entityManager->getRepository('CSBillQuoteBundle:Quote');

            /** @var Quote[] $quotes */
            $quotes = $quoteRepository->findBy(array('id' => $ids));

            /** @var FlashBag $flashBag */
            $flashBag = $this->session->getBag('flashes');

            $failed = 0;
            foreach ($quotes as $quote) {
                $finite = $this->finite->get($quote, Graph::GRAPH);

                if ($finite->can(Graph::TRANSITION_ARCHIVE)) {
                    $quote->archive();
                    $this->entityManager->persist($quote);
                } else {
                    $flashBag->add('warning', 'quote.transition.exception.archive');
                    $failed++;
                }
            }

            if ($failed !== count($quotes)) {
                $this->entityManager->flush();
                $flashBag->add('success', 'quote.archive.success');
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
        return 'CSBillQuoteBundle:Default:index.html.twig';
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