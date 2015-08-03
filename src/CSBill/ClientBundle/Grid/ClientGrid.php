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
use CSBill\ClientBundle\Model\Status;
use CSBill\ClientBundle\Repository\ClientRepository;
use CSBill\DataGridBundle\Action\ActionColumn;
use CSBill\DataGridBundle\Action\Collection;
use CSBill\DataGridBundle\Action\DeleteMassAction;
use CSBill\DataGridBundle\Action\MassAction;
use CSBill\DataGridBundle\Grid\Filters;
use CSBill\DataGridBundle\GridInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ClientGrid implements GridInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param ManagerRegistry  $doctrine
     * @param SessionInterface $session
     */
    public function __construct(ManagerRegistry $doctrine, SessionInterface $session)
    {
        $this->objectManager = $doctrine->getManager();
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return new Entity('CSBillClientBundle:Client');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(Filters $filters)
    {
        $callback = function (QueryBuilder $queryBuilder, $status) {
            $aliases = $queryBuilder->getRootAliases();
            $alias = $aliases[0];

            $queryBuilder->andWhere($alias.'.status = :status')
                ->setParameter('status', $status);
        };

        $filters->add(
            'active_clients',
            function (QueryBuilder $queryBuilder) use ($callback) {
                $callback($queryBuilder, Status::STATUS_ACTIVE);
            }
        );

        $filters->add(
            'inactive_clients',
            function (QueryBuilder $queryBuilder) use ($callback) {
                $callback($queryBuilder, Status::STATUS_INACTIVE);
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

        $queryBuilder->andWhere($aliases[0].'.name LIKE :search')
            ->setParameter('search', "%{$searchString}%");
    }

    /**
     * {@inheritdoc}
     */
    public function getRowActions(Collection $collection)
    {
        $viewAction = new ActionColumn();
        $viewAction->setIcon('eye')
            ->setTitle('client.grid.actions.view')
            ->setRoute('_clients_view')
            ->setClass('primary')
        ;

        $editAction = new ActionColumn();
        $editAction->setIcon('edit')
            ->setTitle('client.grid.actions.edit')
            ->setRoute('_clients_edit')
            ->setClass('info')
        ;

        $collection->add($viewAction);
        $collection->add($editAction);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getMassActions()
    {
        $archive = new MassAction('Archive', function ($ids) {
            /** @var ClientRepository $clientRepository */
            $clientRepository = $this->objectManager->getRepository('CSBillClientBundle:Client');

            /** @var Client[] $clients */
            $clients = $clientRepository->findBy(array('id' => $ids));

            foreach ($clients as $client) {
                $client->archive();
                $client->setStatus(Status::STATUS_ARCHIVED);
                $this->objectManager->persist($client);
            }

            $this->objectManager->flush();

            /** @var FlashBag $flashBag */
            $flashBag = $this->session->getBag('flashes');
            $flashBag->add('success', 'client.archive.success');
        }, true);

        $archive->setIcon('archive');
        $archive->setClass('warning');

        $deleteAction = new DeleteMassAction(true);

        return array(
            $archive,
            $deleteAction,
        );
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

    /**
     * {@inheritdoc}
     */
    public function isFilterable()
    {
        return true;
    }
}
